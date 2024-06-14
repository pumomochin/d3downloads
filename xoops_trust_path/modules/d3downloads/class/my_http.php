<?php

// for get http_header.

if( ! class_exists( 'My_HTTP' ) )
{
	require_once dirname( dirname(__FILE__) ).'/class/post_check.php' ;

	class My_HTTP
	{
		public $proxy_host      = '' ;
		public $proxy_port      = '' ;
		public $proxy_user      = '' ;
		public $proxy_pass      = '' ;
		public $user            = '' ;
		public $pass            = '' ;
		public $maxredirect     =  0 ;
		public $fp_timeout      = 10 ;
		public $read_timeout    = 10 ;
		var	$headers         = array() ;
		var	$response_phrase = '' ;
		var	$status          =  0 ;
		var	$response_code   = '' ;
		var	$redirect_url    = '' ;
		public $result          = false ;
		var	$maxlinelen      = 1024 ;
		public $passcookies     = true ;
		var	$cookies         = array() ;
		public $method          = 'GET' ;
		public $httpversion     = 'HTTP/1.1' ;
		public $is_proxy        = false ;
		public $host            = '' ;
		public $port            = '' ;
		public $curredirect     =  0 ;
		public $error           = '' ;
		public $schemes         = array( 'http', 'https' ) ;

		public function execute( $url )
		{
			if( ! $this->check_url( $url ) ) die( 'invalid request' ) ;

			for( $loop = 0 ; $loop <= $this->maxredirect ; $loop++ ) {
				switch( $loop ) {
					case 0 :
						$this->is_proxy() ;
						$this->headers = $this->get_headers( $url ) ;
					break ;
					default :
						if( empty( $this->headers['Location'] ) ) break 2 ;
						if( $this->check_url( $this->headers['Location'] ) ){
							$this->headers = $this->get_headers( $this->headers['Location'] ) ;
							$this->curredirect++ ;
						}
				}
			}

			$this->result = ( ! empty( $this->headers ) ) ? true : false ;
			if( empty( $this->result ) && empty( $this->error ) ) $this->set_error( 0 , 'Unable To Connect' ) ;
			if( ! empty( $this->result ) ) $this->save_result() ;

			return $this->result ;
		}

		public function get_headers( $url )
		{
			list( $host, $port, $request ) = $this->set_request( $url ) ;

			$this->host = ( empty( $this->is_proxy ) ) ? $host : $this->proxy_host ;
			$this->port = ( empty( $this->is_proxy ) ) ? $port : $this->proxy_port ;

			$handle = @fsockopen( $this->host, $this->port, $errno, $errstr, $this->fp_timeout ) ;
			if ( ! empty( $handle ) ) {
				return $this->parse_headers( $handle, $request ) ;
			} else {
				$this->set_error( $errno ) ;
				return array() ;
			}
		}

		public function set_request( $url )
		{
			list( $host, $port, $path ) = $this->parse_myurl( $url ) ;
			
			$request  = $this->method." ".$path." ".$this->httpversion."\r\n";
			$request .= "Host: ".$host." \r\n" ;

			if ( ! empty( $this->cookies ) ) $request .= $this->cookie_request() ;
			if ( ! empty( $this->user ) || ! empty( $this->pass ) )	$request .= $this->authorization() ;
			if ( ! empty( $this->proxy_user ) ) $request .= $this->proxy_authorization() ;
				
			$request .= "Connection: Close\r\n\r\n" ;

			return array( $host, $port, $request ) ;
		}

		public function parse_myurl( $url )
		{
			$url_info = parse_url( $url ) ;

			$host = ( ! empty( $url_info['host'] ) ) ? $url_info['host'] : '' ;
			$port = ( ! empty( $url_info['port'] ) ) ? $url_info['port'] : 80 ;

			$path = ( ! $this->is_proxy ) ?
					( ! empty( $url_info['path'] ) ? $url_info['path'] : '/' ) . ( ! empty( $url_info['query'] ) ? '?' . $url_info['query'] : '' ) :
					$url ;
			
			if ( ! empty( $url_info['user'] ) ) $this->user = $url_info['user'] ;
			if ( ! empty( $url_info['pass'] ) ) $this->pass = $url_info['pass'] ;

			return array( $host, $port, $path ) ;
		}

		public function cookie_request()
		{
			$cookie_headers = '';

			if( is_array( $this->cookies ) && ! empty( $this->cookies ) ) {
				$cookie_headers .= 'Cookie: ' ;
				foreach ( $this->cookies as $key => $value ) {
					$cookie_headers .= $key."=".urlencode( $value )."; " ;
				}
			}
			return substr( $cookie_headers, 0, -2 ) . "\r\n" ;
		}

		public function authorization()
		{
			return "Authorization: " . "Basic " . base64_encode( $this->user . ":" . $this->pass )."\r\n";
		}

		public function proxy_authorization()
		{
			return "Proxy-Authorization: " . "Basic " . base64_encode( $this->proxy_user . ":" . $this->proxy_pass )."\r\n" ;
		}

		public function parse_headers( $handle, $request )
		{
			$i = 0 ;
			$headers = array() ;

			fwrite( $handle, $request ) ;

			if ( ! stream_set_timeout( $handle, $this->read_timeout ) ) $this->set_error( 0, 'No Response' ) ;
			else while ( ! feof( $handle ) && $line = trim( fgets( $handle, $this->maxlinelen ) ) ){
				if ( $line == "\r\n" ) break ;
				if( $i == 0 ){
					if ( strpos ( $line , 'HTTP' ) === false )  continue ;
					$array = explode( ' ',  $line ) ;
					for( $loop = 0 ; $loop <= 2 ; $loop++ ) {
						switch( $loop ) {
							case 0 :
								$key = 'HTTP-Version' ;
								break ;
							case 1 :
								$key = 'Status-Code' ;
								$headers['Response-Code'] = implode( ' ', $array ) ;
								break ;
							case 2 :
								$key = 'Reason-Phrase' ;
								break ;
						}
						$headers[ $key ] = ( $loop <= 1 ) ? array_shift( $array ) : implode( ' ', $array ) ;
					}
				} else {
					$array = explode( ':', $line ) ;
					$key   = array_shift( $array ) ;
					$headers[ $key ] = substr( implode( ':', $array ), 1 ) ;
					if ( $key == 'Location' && $this->curredirect < $this->maxredirect ) break ;
				}
				$i++ ;
			}
			fclose( $handle ) ;
			if ( ! empty( $this->passcookies ) && ! empty( $headers['Set-Cookie'] ) ) $this->set_cookies( $headers['Set-Cookie'] ) ;
			return $headers ;
		}

		public function check_url( $url )
		{
			$post_check = new Post_Check() ;
			return ( $post_check->urlCheck( $url, $this->schemes ) ) ? true : false ;
		}

		public function is_proxy()
		{
			$this->is_proxy = ( ! empty( $this->proxy_host ) && ! empty( $this->proxy_port ) ) ? true : false ;
		}

		public function set_cookies( $value )
		{
			if( preg_match( '/^([^=]+)=([^;]+)/i', $value, $match ) ) {
				$this->cookies[ $match[1] ] = urldecode( $match[2] ) ;
			}
		}

		public function save_result()
		{
			$this->response_phrase = $this->headers['Reason-Phrase'] ;
			$this->status          = $this->headers['Status-Code'] ;
			$this->response_code   = $this->headers['Response-Code'] ;
			$this->make_redirect_url() ;
		}

		public function make_redirect_url()
		{
			if( ! empty( $this->headers['Location'] ) && $this->check_url( $this->headers['Location'] ) ){
				$location = parse_url( $this->headers['Location'] ) ;
				$redirect_url  = $location['scheme'] . '://' .$location['host'] ;
				$redirect_url .= ( ! empty( $location['path'] ) ) ? $location['path'] : '/'  ;

				$this->redirect_url = $redirect_url ;
			}
		}

		public function set_error( $errno=0, $messege='' )
		{
			if ( ! empty( $messege ) ) $this->error = $messege ;
			else switch( $errno ) {
				case -3 :
					$this->error = 'socket creation failed' ;
					break ;
				case -4 :
					$this->error = 'dns lookup failure' ;
					break ;
				case -5 :
					$this->error = 'connection refused or timed out' ;
					break ;
				default:
					$this->error = 'connection failed' ;
			}
		}
	}
}

?>