<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/** Isolated, request-only HTTP client; never calls SOAP or redirects from an API response. */
final class ZP24_Api {
    private $sandbox;

    public function __construct( $sandbox = false ) {
        $this->sandbox = (bool) $sandbox;
    }

    public function request_payment( $merchant, $amount, $currency, $description, $callback, $metadata = array() ) {
        $body = array(
            'merchant_id'  => $merchant,
            'amount'       => (int) $amount,
            'currency'     => $currency,
            'description'  => $description,
            'callback_url' => $callback,
        );
        if ( ! empty( $metadata ) ) {
            $body['metadata'] = $metadata;
        }
        return $this->post( 'request.json', $body );
    }

    public function verify_payment( $merchant, $amount, $authority ) {
        return $this->post( 'verify.json', array(
            'merchant_id' => $merchant,
            'amount'      => (int) $amount,
            'authority'   => $authority,
        ) );
    }

    private function post( $endpoint, $body ) {
        $host = $this->sandbox ? 'sandbox.zarinpal.com' : 'payment.zarinpal.com';
        $url  = 'https://' . $host . '/pg/v4/payment/' . $endpoint;
        $response = wp_remote_post( $url, array(
            'timeout'     => 20,
            'redirection' => 0,
            'sslverify'   => true,
            'headers'     => array(
                'Accept'       => 'application/json',
                'Content-Type' => 'application/json',
            ),
            'body'        => wp_json_encode( $body ),
        ) );

        if ( is_wp_error( $response ) ) {
            return new WP_Error( 'zp24_network', 'ارتباط امن با زرین‌پال برقرار نشد. وضعیت تراکنش را بررسی کنید.' );
        }
        $http = (int) wp_remote_retrieve_response_code( $response );
        $raw  = wp_remote_retrieve_body( $response );
        $json = json_decode( $raw, true );
        if ( ! is_array( $json ) ) {
            return new WP_Error( 'zp24_invalid_response', 'پاسخ معتبر JSON از زرین‌پال دریافت نشد.' );
        }
        $data = isset( $json['data'] ) && is_array( $json['data'] ) ? $json['data'] : array();
        $code = isset( $data['code'] ) ? (int) $data['code'] : 0;
        if ( $code === 0 && ! empty( $json['errors'] ) ) {
            $errors = $json['errors'];
            if ( is_array( $errors ) && isset( $errors['code'] ) ) {
                $code = (int) $errors['code'];
            }
        }
        if ( $http < 200 || $http > 299 ) {
            return new WP_Error( 'zp24_http_' . $http, 'پاسخ ناموفق از سرویس زرین‌پال (HTTP ' . $http . ', code ' . $code . ').', array( 'code' => $code ) );
        }
        if ( ! isset( $data['code'] ) ) {
            return new WP_Error( 'zp24_api_error', 'درگاه نتیجه معتبر برنگرداند (code ' . $code . ').', array( 'code' => $code ) );
        }
        return $data;
    }

    public function startpay_url( $authority ) {
        $host = $this->sandbox ? 'sandbox.zarinpal.com' : 'payment.zarinpal.com';
        return 'https://' . $host . '/pg/StartPay/' . rawurlencode( $authority );
    }
}
