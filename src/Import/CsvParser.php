<?php

declare(strict_types=1);

namespace WagadaDigital\YoastMetadata\Import;

use Exception;

/**
 * Parses and validates CSV files for import.
 */
final class CsvParser {

    /**
     * Required CSV headers.
     */
    private const REQUIRED_HEADERS = [ 'url' ];

    /**
     * Optional CSV headers.
     */
    private const OPTIONAL_HEADERS = [ 'title', 'description', 'focuskw', 'canonical', 'post_type' ];

    /**
     * Parsed headers from the CSV.
     *
     * @var array<int, string>
     */
    private array $headers = [];

    /**
     * Parse a CSV file and return rows.
     *
     * @param string $file_path Path to the CSV file.
     * @return array<int, array<string, string>> Parsed rows with headers as keys.
     * @throws Exception If file cannot be read or is invalid.
     */
    public function parse( string $file_path ): array {
        if ( ! file_exists( $file_path ) || ! is_readable( $file_path ) ) {
            throw new Exception( __( 'CSV file not found or not readable.', 'yoast-metadata' ) );
        }

        $handle = fopen( $file_path, 'r' );
        if ( false === $handle ) {
            throw new Exception( __( 'Could not open CSV file.', 'yoast-metadata' ) );
        }

        $rows = [];

        // Read header row.
        $header_row = fgetcsv( $handle );
        if ( false === $header_row || empty( $header_row ) ) {
            fclose( $handle );
            throw new Exception( __( 'CSV file is empty or has no headers.', 'yoast-metadata' ) );
        }

        $this->headers = array_map( 'strtolower', array_map( 'trim', $header_row ) );
        $this->validate_headers();

        // Read data rows.
        while ( ( $row = fgetcsv( $handle ) ) !== false ) {
            if ( $this->is_empty_row( $row ) ) {
                continue;
            }

            $parsed_row = $this->parse_row( $row );
            if ( null !== $parsed_row ) {
                $rows[] = $parsed_row;
            }
        }

        fclose( $handle );

        return $rows;
    }

    /**
     * Validate that required headers are present.
     *
     * @throws Exception If required headers are missing.
     */
    private function validate_headers(): void {
        $missing = array_diff( self::REQUIRED_HEADERS, $this->headers );
        if ( ! empty( $missing ) ) {
            throw new Exception(
                sprintf(
                    /* translators: %s: comma-separated list of missing headers */
                    __( 'Missing required CSV headers: %s', 'yoast-metadata' ),
                    implode( ', ', $missing )
                )
            );
        }
    }

    /**
     * Check if a row is empty.
     *
     * @param array<int, string|null> $row CSV row.
     */
    private function is_empty_row( array $row ): bool {
        return empty( array_filter( $row, fn( $cell ) => null !== $cell && '' !== trim( (string) $cell ) ) );
    }

    /**
     * Parse a single row into an associative array.
     *
     * @param array<int, string|null> $row CSV row.
     * @return array<string, string>|null Parsed row or null if invalid.
     */
    private function parse_row( array $row ): ?array {
        $parsed = [];

        foreach ( $this->headers as $index => $header ) {
            $value = isset( $row[ $index ] ) ? trim( (string) $row[ $index ] ) : '';
            $parsed[ $header ] = $value;
        }

        // Skip rows without URL.
        if ( empty( $parsed['url'] ) ) {
            return null;
        }

        return $parsed;
    }

    /**
     * Get the detected headers.
     *
     * @return array<int, string>
     */
    public function get_headers(): array {
        return $this->headers;
    }

    /**
     * Chunk rows into batches for processing.
     *
     * @param array<int, array<string, string>> $rows      All rows.
     * @param int                                $batch_size Rows per batch.
     * @return array<int, array<int, array<string, string>>> Chunked rows.
     */
    public function chunk_rows( array $rows, int $batch_size = 50 ): array {
        return array_chunk( $rows, $batch_size );
    }
}
