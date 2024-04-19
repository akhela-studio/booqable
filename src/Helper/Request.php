<?php

namespace Booqable\Helper;

use \Firebase\JWT\JWT;


class Request
{
    private static $default_api_version = 'boomerang';

    /**
     * @param $fullpath
     * @param $request_method
     * @param $body
     * @return string
     */
    private static function sign($fullpath, $request_method, $body)
    {
        $options = Options::get();

        $privateKey = file_get_contents(BOOQABLE_KEY_PATH);

        if( is_array($body) )
            $body = json_encode($body);

        $encoded_body = base64_encode(hash('sha256', $body, true));

        $data = base64_encode(hash('sha256', $request_method.'.'.$fullpath.'.'.$encoded_body, true));
        $uuid = uniqid();

        $headers = ['kind'=>'single_use'];
        $time = time();

        $payload =[
            'iss'=> 'https://'.$options['domain'],
            'sub'=> $options['employee-id'],
            'aud'=> $options['company-id'],
            'exp'=> $time + 600,
            'iat'=> $time,
            'jti'=> $uuid.'.'.$data
        ];

        return JWT::encode($payload, $privateKey, 'RS256', $options['single-use-token'], $headers);
    }

    /**
     * @param $path
     * @param $request_method
     * @param array $body
     * @param array $url_params
     * @param bool $api_version
     * @return void
     * @throws \Exception
     */
    private static function request($path, $request_method, $body=[], $url_params=[], $api_version=false)
    {
        $options = Options::get();

        if( !$api_version )
            $api_version = self::$default_api_version;

        $fullpath = '/api/'.$api_version.$path;

        if( $request_method  == 'GET' && !empty($body) && empty($url_params) )
            $url_params = $body;

        if( $api_version == 1 )
            $url_params['api_key'] = $options['token'];

        if( !empty($url_params) )
            $fullpath .= '?'.http_build_query($url_params);

        $headers = [
            'Content-Type: application/json'
        ];

        if( $api_version == 'boomerang' ){

            $token = self::sign($fullpath, $request_method, $body);
            $headers[] = 'Authorization: Bearer '.$token;
        }

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, 'https://'.$options['domain'].$fullpath);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $request_method);

        if( $request_method  == 'POST' ||  $request_method  == 'PUT' )
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $response = curl_exec($ch);

        if ( curl_errno($ch) )
            throw new \Exception(curl_error($ch), 500);

        curl_close($ch);

        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        $response = json_decode($response, true);

        if( $httpcode >= 400 ){

            if( $httpcode == 404 )
                throw new \Exception($response['errors'][0]['title']??'Resource(s) not found', $httpcode);
            else
                throw new \Exception($response['errors'][0]['detail']??'Unknown error', $httpcode);
        }

        return $response;
    }

    /**
     * @param $path
     * @param array $query
     * @param bool $api_version
     * @return null
     * @throws \Exception
     */
    public static function get($path, $query=[], $api_version=false)
    {
        return self::request($path, 'GET', [], $query, $api_version);
    }

    /**
     * @param $path
     * @param array $body
     * @param array $url_params
     * @param bool $api_version
     * @return null
     * @throws \Exception
     */
    public static function post($path, $body=[], $url_params=[], $api_version=false)
    {
        return self::request($path, 'POST', $body, $url_params, $api_version);
    }

    /**
     * @param $path
     * @param array $body
     * @param array $url_params
     * @param bool $api_version
     * @return null
     * @throws \Exception
     */
    public static function put($path, $body=[], $url_params=[], $api_version=false)
    {
        return self::request($path, 'PUT', $body, $url_params, $api_version);
    }

    /**
     * @param $path
     * @param array $body
     * @param array $url_params
     * @param bool $api_version
     * @return null
     * @throws \Exception
     */
    public static function patch($path, $body=[], $url_params=[], $api_version=false)
    {
        return self::request($path, 'PATCH', $body, $url_params, $api_version);
    }

    /**
     * @param $path
     * @param array $body
     * @param array $url_params
     * @param bool $api_version
     * @return null
     * @throws \Exception
     */
    public static function delete($path, $body=[], $url_params=[], $api_version=false)
    {
        return self::request($path, 'PATCH', $body, $url_params, $api_version);
    }
}
