/**
 * Yoast Metadata Admin JavaScript
 */
(function($) {
    'use strict';

    const YoastMetadata = {
        importId: null,
        isProcessing: false,

        init: function() {
            this.bindEvents();
        },

        bindEvents: function() {
            // File upload
            $('#yoast-metadata-csv-file').on('change', this.handleFileSelect.bind(this));

            // Drag and drop
            const uploadArea = $('.yoast-metadata-upload-area');
            uploadArea.on('dragover dragenter', this.handleDragOver.bind(this));
            uploadArea.on('dragleave dragend drop', this.handleDragLeave.bind(this));
            uploadArea.on('drop', this.handleDrop.bind(this));

            // Start import button
            $('#yoast-metadata-start-import').on('click', this.startImport.bind(this));

            // Export form
            $('#yoast-metadata-export-form').on('submit', this.handleExport.bind(this));

            // Post type checkboxes for export count
            $('input[name="post_types[]"]').on('change', this.updateExportCount.bind(this));
            $('#yoast-metadata-empty-meta').on('change', this.updateExportCount.bind(this));

            // Initialize export count if on export tab
            if ($('#yoast-metadata-export-count').length) {
                this.updateExportCount();
            }
        },

        handleDragOver: function(e) {
            e.preventDefault();
            e.stopPropagation();
            $('.yoast-metadata-upload-area').addClass('drag-over');
        },

        handleDragLeave: function(e) {
            e.preventDefault();
            e.stopPropagation();
            $('.yoast-metadata-upload-area').removeClass('drag-over');
        },

        handleDrop: function(e) {
            e.preventDefault();
            e.stopPropagation();
            $('.yoast-metadata-upload-area').removeClass('drag-over');

            const files = e.originalEvent.dataTransfer.files;
            if (files.length > 0) {
                $('#yoast-metadata-csv-file')[0].files = files;
                this.handleFileSelect();
            }
        },

        handleFileSelect: function() {
            const fileInput = $('#yoast-metadata-csv-file')[0];
            const file = fileInput.files[0];

            if (!file) {
                return;
            }

            // Validate file type
            if (!file.name.endsWith('.csv')) {
                this.showNotice('error', yoastMetadata.i18n.error + ' Please upload a CSV file.');
                return;
            }

            this.uploadFile(file);
        },

        uploadFile: function(file) {
            const formData = new FormData();
            formData.append('action', 'yoast_metadata_upload_csv');
            formData.append('nonce', yoastMetadata.nonce);
            formData.append('csv_file', file);

            this.showProgress(0, yoastMetadata.i18n.processing);

            $.ajax({
                url: yoastMetadata.ajaxUrl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: (response) => {
                    this.hideProgress();
                    if (response.success) {
                        this.importId = response.data.import_id;
                        this.showPreview(response.data);
                    } else {
                        this.showNotice('error', response.data.message);
                    }
                },
                error: () => {
                    this.hideProgress();
                    this.showNotice('error', yoastMetadata.i18n.error);
                }
            });
        },

        showPreview: function(data) {
            const preview = $('.yoast-metadata-preview');
            const table = preview.find('.yoast-metadata-preview-table');

            // Clear existing content
            table.find('thead, tbody').empty();

            // Build header
            const headerRow = $('<tr>');
            data.headers.forEach(header => {
                headerRow.append($('<th>').text(header));
            });
            table.find('thead').append(headerRow);

            // Build body
            data.preview.forEach(row => {
                const tr = $('<tr>');
                data.headers.forEach(header => {
                    tr.append($('<td>').text(row[header] || ''));
                });
                table.find('tbody').append(tr);
            });

            // Update info
            $('#yoast-metadata-total-rows').text(data.total);

            // Show preview and import button
            preview.addClass('show');
            $('#yoast-metadata-start-import').show();
        },

        startImport: function(e) {
            e.preventDefault();

            if (this.isProcessing || !this.importId) {
                return;
            }

            if (!confirm(yoastMetadata.i18n.confirmStart)) {
                return;
            }

            this.isProcessing = true;
            $('#yoast-metadata-start-import').prop('disabled', true);

            this.hideResults();
            this.processBatch();
        },

        processBatch: function() {
            $.ajax({
                url: yoastMetadata.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'yoast_metadata_process_batch',
                    nonce: yoastMetadata.nonce,
                    import_id: this.importId
                },
                success: (response) => {
                    if (response.success) {
                        const data = response.data;
                        const percent = Math.round((data.processed / data.total) * 100);

                        this.showProgress(percent, `${data.processed} / ${data.total}`);

                        if (data.complete) {
                            this.importComplete(data);
                        } else {
                            // Continue processing
                            setTimeout(() => this.processBatch(), 100);
                        }
                    } else {
                        this.importError(response.data.message);
                    }
                },
                error: () => {
                    this.importError(yoastMetadata.i18n.error);
                }
            });
        },

        importComplete: function(data) {
            this.isProcessing = false;
            this.importId = null;

            this.hideProgress();
            $('#yoast-metadata-start-import').prop('disabled', false).hide();
            $('.yoast-metadata-preview').removeClass('show');

            // Show results
            this.showResults(data.totals);

            // Reset file input
            $('#yoast-metadata-csv-file').val('');
        },

        importError: function(message) {
            this.isProcessing = false;
            this.hideProgress();
            $('#yoast-metadata-start-import').prop('disabled', false);
            this.showNotice('error', message);
        },

        showProgress: function(percent, text) {
            const progress = $('.yoast-metadata-progress');
            progress.addClass('show');
            progress.find('.yoast-metadata-progress-fill').css('width', percent + '%');
            progress.find('.yoast-metadata-progress-text').text(text || '');
        },

        hideProgress: function() {
            $('.yoast-metadata-progress').removeClass('show');
        },

        showResults: function(totals) {
            const results = $('.yoast-metadata-results');
            results.find('.yoast-metadata-result-items').empty();

            if (totals.updated > 0) {
                results.find('.yoast-metadata-result-items').append(
                    $('<div class="yoast-metadata-result-item success">')
                        .html('<strong>' + yoastMetadata.i18n.updated + '</strong> ' + totals.updated)
                );
            }

            if (totals.skipped > 0) {
                results.find('.yoast-metadata-result-items').append(
                    $('<div class="yoast-metadata-result-item warning">')
                        .html('<strong>' + yoastMetadata.i18n.skipped + '</strong> ' + totals.skipped)
                );
            }

            if (totals.failed > 0) {
                results.find('.yoast-metadata-result-items').append(
                    $('<div class="yoast-metadata-result-item error">')
                        .html('<strong>' + yoastMetadata.i18n.failed + '</strong> ' + totals.failed)
                );
            }

            results.addClass('show');
        },

        hideResults: function() {
            $('.yoast-metadata-results').removeClass('show');
        },

        showNotice: function(type, message) {
            const notice = $('<div class="notice notice-' + type + ' is-dismissible"><p>' + message + '</p></div>');
            $('.yoast-metadata-wrap h1').after(notice);

            // Auto dismiss after 5 seconds
            setTimeout(() => notice.fadeOut(() => notice.remove()), 5000);
        },

        handleExport: function(e) {
            // Form submits normally for file download
            // Just validate that at least one post type is selected
            const checked = $('input[name="post_types[]"]:checked').length;
            if (checked === 0) {
                e.preventDefault();
                this.showNotice('error', 'Please select at least one post type.');
            }
        },

        updateExportCount: function() {
            const formData = $('#yoast-metadata-export-form').serialize();

            $.ajax({
                url: yoastMetadata.ajaxUrl,
                type: 'POST',
                data: formData + '&action=yoast_metadata_get_export_count&nonce=' + yoastMetadata.nonce,
                success: (response) => {
                    if (response.success) {
                        $('#yoast-metadata-export-count').text(response.data.count + ' posts will be exported');
                    }
                }
            });
        }
    };

    $(document).ready(function() {
        YoastMetadata.init();
    });

})(jQuery);
