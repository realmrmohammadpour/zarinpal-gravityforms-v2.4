<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/** Adapt the legacy Persian Gravity Forms notification listener only during our payment action. */
final class ZP24_Persian_Compat {
    const HOOK = 'gform_post_payment_action';

    private static function needs_adapter( $callback ) {
        if ( ! is_array( $callback ) || ! isset( $callback[0], $callback[1] )
            || ! is_object( $callback[0] )
            || ! is_a( $callback[0], 'GFPersian_Snippets' )
            || 'send_payment_status_notification' !== $callback[1] ) {
            return false;
        }
        try {
            $method = new ReflectionMethod( $callback[0], $callback[1] );
            $params = $method->getParameters();
            $type   = isset( $params[1] ) ? $params[1]->getType() : null;
            return $method->isPublic() && $type instanceof ReflectionNamedType && 'string' === $type->getName();
        } catch ( ReflectionException $exception ) {
            return false;
        }
    }

    /** Keep the native GF action array intact for every other registered callback. */
    public static function run( callable $operation ) {
        global $wp_filter;
        $replaced = array();
        try {
            if ( isset( $wp_filter[ self::HOOK ] ) && $wp_filter[ self::HOOK ] instanceof WP_Hook ) {
                $snapshot = $wp_filter[ self::HOOK ]->callbacks;
                foreach ( $snapshot as $priority => $callbacks ) {
                    foreach ( $callbacks as $registered ) {
                        $original = $registered['function'];
                        $accepted = (int) $registered['accepted_args'];
                        if ( $accepted < 2 || ! self::needs_adapter( $original ) ) {
                            continue;
                        }
                        $adapter = static function ( $entry, $action = null ) use ( $original ) {
                            $event = is_array( $action ) ? ( $action['type'] ?? null ) : $action;
                            // Never map a missing, invalid or unrelated event to fail_payment.
                            if ( ! is_array( $entry ) || ! in_array( $event, array( 'complete_payment', 'fail_payment' ), true ) ) {
                                return;
                            }
                            // Reuse the original notification, conditional-logic and deduplication code.
                            call_user_func( $original, $entry, $event );
                        };
                        if ( remove_action( self::HOOK, $original, $priority ) ) {
                            $replaced[] = array( $original, $adapter, $priority, $accepted );
                            add_action( self::HOOK, $adapter, $priority, 2 );
                        }
                    }
                }
            }
            return $operation();
        } finally {
            // Do not change Persian Gravity Forms behavior for any unrelated request or gateway.
            foreach ( $replaced as $replacement ) {
                list( $original, $adapter, $priority, $accepted ) = $replacement;
                remove_action( self::HOOK, $adapter, $priority );
                add_action( self::HOOK, $original, $priority, $accepted );
            }
        }
    }
}
