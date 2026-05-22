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
            const uploadArea = $('.yoast-metadata-upload-area').not('#yoast-metadata-taxonomy-upload-area');
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

            // Taxonomy import/export events
            this.bindTaxonomyEvents();
        },

        // Taxonomy-specific bindings
        bindTaxonomyEvents: function() {
            // Taxonomy file upload
            $('#yoast-metadata-taxonomy-csv-file').on('change', this.handleTaxonomyFileSelect.bind(this));

            // Taxonomy drag and drop
            const taxonomyUploadArea = $('#yoast-metadata-taxonomy-upload-area');
            taxonomyUploadArea.on('dragover dragenter', this.handleTaxonomyDragOver.bind(this));
            taxonomyUploadArea.on('dragleave dragend drop', this.handleTaxonomyDragLeave.bind(this));
            taxonomyUploadArea.on('drop', this.handleTaxonomyDrop.bind(this));

            // Start taxonomy import button
            $('#yoast-metadata-start-taxonomy-import').on('click', this.startTaxonomyImport.bind(this));

            // Taxonomy export form
            $('#yoast-metadata-taxonomy-export-form').on('submit', this.handleTaxonomyExport.bind(this));

            // Taxonomy checkboxes for export count
            $('input[name="taxonomies[]"]').on('change', this.updateTaxonomyExportCount.bind(this));
            $('#yoast-metadata-taxonomy-empty-meta').on('change', this.updateTaxonomyExportCount.bind(this));

            // Initialize taxonomy export count if on taxonomy export tab
            if ($('#yoast-metadata-taxonomy-export-count').length) {
                this.updateTaxonomyExportCount();
            }
        },

        handleDragOver: function(e) {
            e.preventDefault();
            e.stopPropagation();
            $('.yoast-metadata-upload-area').not('#yoast-metadata-taxonomy-upload-area').addClass('drag-over');
        },

        handleDragLeave: function(e) {
            e.preventDefault();
            e.stopPropagation();
            $('.yoast-metadata-upload-area').not('#yoast-metadata-taxonomy-upload-area').removeClass('drag-over');
        },

        handleDrop: function(e) {
            e.preventDefault();
            e.stopPropagation();
            $('.yoast-metadata-upload-area').not('#yoast-metadata-taxonomy-upload-area').removeClass('drag-over');

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

            // Add skip_existing option
            if ($('#yoast-metadata-skip-existing').is(':checked')) {
                formData.append('skip_existing', '1');
            }

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
            const preview = $('.yoast-metadata-preview').not('#yoast-metadata-taxonomy-preview');
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
            $('.yoast-metadata-preview').not('#yoast-metadata-taxonomy-preview').removeClass('show');

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
            const progress = $('.yoast-metadata-progress').not('#yoast-metadata-taxonomy-progress');
            progress.addClass('show');
            progress.find('.yoast-metadata-progress-fill').css('width', percent + '%');
            progress.find('.yoast-metadata-progress-text').text(text || '');
        },

        hideProgress: function() {
            $('.yoast-metadata-progress').not('#yoast-metadata-taxonomy-progress').removeClass('show');
        },

        showResults: function(totals) {
            const results = $('.yoast-metadata-results').not('#yoast-metadata-taxonomy-results');
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
                let failedHtml = '<strong>' + yoastMetadata.i18n.failed + '</strong> ' + totals.failed;
                if (totals.failed_urls && totals.failed_urls.length > 0) {
                    failedHtml += '<details style="margin-top:6px"><summary style="cursor:pointer">Show failed URLs</summary><ul style="margin:6px 0 0 16px;padding:0">';
                    totals.failed_urls.forEach(url => {
                        failedHtml += '<li style="font-size:12px;word-break:break-all;margin-bottom:2px">' + url + '</li>';
                    });
                    failedHtml += '</ul></details>';
                }
                results.find('.yoast-metadata-result-items').append(
                    $('<div class="yoast-metadata-result-item error">').html(failedHtml)
                );
            }

            results.addClass('show');
        },

        hideResults: function() {
            $('.yoast-metadata-results').not('#yoast-metadata-taxonomy-results').removeClass('show');
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
        },

        // ===========================================
        // Taxonomy-specific methods
        // ===========================================

        taxonomyImportId: null,
        isTaxonomyProcessing: false,

        handleTaxonomyDragOver: function(e) {
            e.preventDefault();
            e.stopPropagation();
            $('#yoast-metadata-taxonomy-upload-area').addClass('drag-over');
        },

        handleTaxonomyDragLeave: function(e) {
            e.preventDefault();
            e.stopPropagation();
            $('#yoast-metadata-taxonomy-upload-area').removeClass('drag-over');
        },

        handleTaxonomyDrop: function(e) {
            e.preventDefault();
            e.stopPropagation();
            $('#yoast-metadata-taxonomy-upload-area').removeClass('drag-over');

            const files = e.originalEvent.dataTransfer.files;
            if (files.length > 0) {
                $('#yoast-metadata-taxonomy-csv-file')[0].files = files;
                this.handleTaxonomyFileSelect();
            }
        },

        handleTaxonomyFileSelect: function() {
            const fileInput = $('#yoast-metadata-taxonomy-csv-file')[0];
            const file = fileInput.files[0];

            if (!file) {
                return;
            }

            // Validate file type
            if (!file.name.endsWith('.csv')) {
                this.showNotice('error', yoastMetadata.i18n.error + ' Please upload a CSV file.');
                return;
            }

            this.uploadTaxonomyFile(file);
        },

        uploadTaxonomyFile: function(file) {
            const formData = new FormData();
            formData.append('action', 'yoast_metadata_upload_taxonomy_csv');
            formData.append('nonce', yoastMetadata.nonce);
            formData.append('csv_file', file);

            // Add skip_existing option
            if ($('#yoast-metadata-taxonomy-skip-existing').is(':checked')) {
                formData.append('skip_existing', '1');
            }

            this.showTaxonomyProgress(0, yoastMetadata.i18n.processing);

            $.ajax({
                url: yoastMetadata.ajaxUrl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: (response) => {
                    this.hideTaxonomyProgress();
                    if (response.success) {
                        this.taxonomyImportId = response.data.import_id;
                        this.showTaxonomyPreview(response.data);
                    } else {
                        this.showNotice('error', response.data.message);
                    }
                },
                error: () => {
                    this.hideTaxonomyProgress();
                    this.showNotice('error', yoastMetadata.i18n.error);
                }
            });
        },

        showTaxonomyPreview: function(data) {
            const preview = $('#yoast-metadata-taxonomy-preview');
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
            $('#yoast-metadata-taxonomy-total-rows').text(data.total);

            // Show preview and import button
            preview.addClass('show');
            $('#yoast-metadata-start-taxonomy-import').show();
        },

        startTaxonomyImport: function(e) {
            e.preventDefault();

            if (this.isTaxonomyProcessing || !this.taxonomyImportId) {
                return;
            }

            if (!confirm(yoastMetadata.i18n.confirmStart)) {
                return;
            }

            this.isTaxonomyProcessing = true;
            $('#yoast-metadata-start-taxonomy-import').prop('disabled', true);

            this.hideTaxonomyResults();
            this.processTaxonomyBatch();
        },

        processTaxonomyBatch: function() {
            $.ajax({
                url: yoastMetadata.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'yoast_metadata_process_taxonomy_batch',
                    nonce: yoastMetadata.nonce,
                    import_id: this.taxonomyImportId
                },
                success: (response) => {
                    if (response.success) {
                        const data = response.data;
                        const percent = Math.round((data.processed / data.total) * 100);

                        this.showTaxonomyProgress(percent, `${data.processed} / ${data.total}`);

                        if (data.complete) {
                            this.taxonomyImportComplete(data);
                        } else {
                            // Continue processing
                            setTimeout(() => this.processTaxonomyBatch(), 100);
                        }
                    } else {
                        this.taxonomyImportError(response.data.message);
                    }
                },
                error: () => {
                    this.taxonomyImportError(yoastMetadata.i18n.error);
                }
            });
        },

        taxonomyImportComplete: function(data) {
            this.isTaxonomyProcessing = false;
            this.taxonomyImportId = null;

            this.hideTaxonomyProgress();
            $('#yoast-metadata-start-taxonomy-import').prop('disabled', false).hide();
            $('#yoast-metadata-taxonomy-preview').removeClass('show');

            // Show results
            this.showTaxonomyResults(data.totals);

            // Reset file input
            $('#yoast-metadata-taxonomy-csv-file').val('');
        },

        taxonomyImportError: function(message) {
            this.isTaxonomyProcessing = false;
            this.hideTaxonomyProgress();
            $('#yoast-metadata-start-taxonomy-import').prop('disabled', false);
            this.showNotice('error', message);
        },

        showTaxonomyProgress: function(percent, text) {
            const progress = $('#yoast-metadata-taxonomy-progress');
            progress.addClass('show');
            progress.find('.yoast-metadata-progress-fill').css('width', percent + '%');
            progress.find('.yoast-metadata-progress-text').text(text || '');
        },

        hideTaxonomyProgress: function() {
            $('#yoast-metadata-taxonomy-progress').removeClass('show');
        },

        showTaxonomyResults: function(totals) {
            const results = $('#yoast-metadata-taxonomy-results');
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
                let failedHtml = '<strong>' + yoastMetadata.i18n.failed + '</strong> ' + totals.failed;
                if (totals.failed_urls && totals.failed_urls.length > 0) {
                    failedHtml += '<details style="margin-top:6px"><summary style="cursor:pointer">Show failed URLs</summary><ul style="margin:6px 0 0 16px;padding:0">';
                    totals.failed_urls.forEach(url => {
                        failedHtml += '<li style="font-size:12px;word-break:break-all;margin-bottom:2px">' + url + '</li>';
                    });
                    failedHtml += '</ul></details>';
                }
                results.find('.yoast-metadata-result-items').append(
                    $('<div class="yoast-metadata-result-item error">').html(failedHtml)
                );
            }

            results.addClass('show');
        },

        hideTaxonomyResults: function() {
            $('#yoast-metadata-taxonomy-results').removeClass('show');
        },

        handleTaxonomyExport: function(e) {
            // Form submits normally for file download
            // Just validate that at least one taxonomy is selected
            const checked = $('input[name="taxonomies[]"]:checked').length;
            if (checked === 0) {
                e.preventDefault();
                this.showNotice('error', 'Please select at least one taxonomy.');
            }
        },

        updateTaxonomyExportCount: function() {
            const formData = $('#yoast-metadata-taxonomy-export-form').serialize();

            $.ajax({
                url: yoastMetadata.ajaxUrl,
                type: 'POST',
                data: formData + '&action=yoast_metadata_get_taxonomy_export_count&nonce=' + yoastMetadata.nonce,
                success: (response) => {
                    if (response.success) {
                        $('#yoast-metadata-taxonomy-export-count').text(response.data.count + ' terms will be exported');
                    }
                }
            });
        }
    };

    $(document).ready(function() {
        YoastMetadata.init();
    });

})(jQuery);
