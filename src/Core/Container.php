<?php

declare(strict_types=1);

namespace Holo\YoastMetadata\Core;

use Closure;
use Exception;

/**
 * Simple dependency injection container.
 */
final class Container {

    /**
     * Registered service definitions.
     *
     * @var array<string, mixed>
     */
    private array $definitions = [];

    /**
     * Resolved service instances.
     *
     * @var array<string, mixed>
     */
    private array $instances = [];

    /**
     * Register a service definition.
     *
     * @param string         $id         Service identifier.
     * @param mixed|Closure  $definition Service definition or factory.
     */
    public function set( string $id, $definition ): void {
        $this->definitions[ $id ] = $definition;
        unset( $this->instances[ $id ] );
    }

    /**
     * Get a service from the container.
     *
     * @param string $id Service identifier.
     * @return mixed
     * @throws Exception If service not found.
     */
    public function get( string $id ) {
        if ( isset( $this->instances[ $id ] ) ) {
            return $this->instances[ $id ];
        }

        if ( ! isset( $this->definitions[ $id ] ) ) {
            throw new Exception( sprintf( 'Service "%s" not found in container.', $id ) );
        }

        $definition = $this->definitions[ $id ];

        if ( $definition instanceof Closure ) {
            $this->instances[ $id ] = $definition( $this );
        } else {
            $this->instances[ $id ] = $definition;
        }

        return $this->instances[ $id ];
    }

    /**
     * Check if a service is registered.
     *
     * @param string $id Service identifier.
     */
    public function has( string $id ): bool {
        return isset( $this->definitions[ $id ] );
    }
}
