<?php
/**
 * OAuth provider class: Salesforce
 *      Using the OAuth class to establish authentication and identity
 *      details from a given provider
 *      
 * @package     OAuth
 * @author      Nick Worth
 */
if ( ! defined( 'ABSPATH' ) ) exit;
 
class OAuth_Salesforce extends OAuthProvider{

	# Provider constants
	const PROVIDER = 'salesforce';

	# Provider settings
	protected $features;
	protected $environment;
	
    /**
     * Provider Constructor
     */
    public function __construct() {
        # Configure the OAuthProvider
        $this->init();
        # Register any provider specific filters
        $this->filters();
        # Register any provider specific actions
        $this->actions();
    }
 
    function init(){
    	# Provider settings
        $this->provider 		= $this::PROVIDER;
        $this->features         = (get_option('options_salesforce_features')) ? get_option('options_salesforce_features') : array();
		$this->environment 		= get_option('options_salesforce_environment');
		
		# Provider authentication
        $this->client_id 		= get_option('options_'.$this->provider.'_client_id');
        $this->client_secret 	= get_option('options_'.$this->provider.'_client_secret');
        $this->redirect_uri 	= get_bloginfo('url').'/oauth/'.$this->provider;
		
		# OAuth URLs
        $this->auth_url 		= $this->getLoginURI().'/services/oauth2/authorize'; 
        $this->tokens_url 		= $this->getLoginURI().'/services/oauth2/token';	# cURL request does not require "?" within the URL

        # Install provider (DO NOT REMOVE)
		parent::install();
    }
 	
 	/**
 	 * Hook into any filters specific to this provider (optional)
 	 * 
 	 * NOTE: This is only necessary if you are modifying the 
 	 * 		default values of parameters used throughout the
 	 * 		OAuth process
 	 */
    function filters(){
        add_filter( 'oauth_authorization_parameters', array( $this, 'authorization_parameters'), 1, 1 );
        // add_filter( 'oauth_token_parameters', array( $this, 'token_parameters'), 1, 1 );
        add_filter( 'oauth_token_response', array( $this, 'token_response'), 1, 1 );
        // add_filter( 'oauth_identity_parameters', array( $this, 'identity_parameters'), 1, 1 );
    	add_filter( 'oauth_identity_response', array( $this, 'identity_response'), 1, 1 );
    }

        /**
         * Modify/extend the authorization parameters
         *
         * @param      array  $params
         */
        public function authorization_parameters($params){
            $params['client_secret'] = DIV\services\helper::decrypt($this->client_secret);
            return $params;
        }

    	/**
    	 * Modify/extend the token response parameters
         * 
         * @param      array  $params
    	 */
    	public function token_response($params){
            $params['refresh_token']     = 'refresh_token';
            $params['issued_at']         = 'issued_at';
    		$params['signature'] 	     = 'signature';
    		$params['scope'] 		     = 'scope';
    		$params['id_token'] 	     = 'id_token';
    		$params['instance_url']      = 'instance_url';
            $params['id']                = 'id';
    		$params['error_description'] = 'error_description';
    		return $params;
    	}

    	/**
    	 * Modify/extend the identity response parameters
         * 
         * @param      array  $params
    	 */
    	public function identity_response($params){
    		$params['organization_id'] 	= 'organization_id';
    		$params['first_name'] 		= 'first_name';
    		$params['last_name'] 		= 'last_name';
    		$params['display_name'] 	= 'display_name';
    		$params['username'] 		= 'username';
    		$params['timezone'] 		= 'timezone';
    		return $params;
    	}

    /**
 	 * Hook into any actions specific to this provider (optional)
 	 * 
 	 * NOTE: This is only necessary if you are modifying the 
 	 * 		default values of parameters used throughout the
 	 * 		OAuth process
 	 */
    function actions(){
    	add_action( 'consume_token_response', array( $this, 'consume_token'), 1, 2 );
    	// add_action( 'consume_identity_response', array( $this, 'consume_identity'), 1, 2 );
    }
    
    	/**
    	 * Setup custom token consumption methods
    	 *
    	 * @param      array  $params
    	 * @param      array  $response
    	 */
    	public function consume_token($params, $response){
            $this->set_field('refresh_token', $response[ $params['refresh_token'] ]);
            $this->set_field('issues_at', date("m/d/Y H:i:s", time( $response[ $params['issued_at'] ]) ));

    		# Used for gateway to Force.com's Identity Service
    		$this->identity_url = $response[ $params['id'] ];
    		
    		$this->set_field('instance_url', $response[ $params['instance_url'] ]);
    		$this->set_field('scope', $response[ $params['scope'] ]);
    		
    	}

    	/**
    	 * Setup custom identity consumption methods
    	 *
    	 * @param      array  $params
    	 * @param      array  $response
    	 */
    	public function consume_identity($params, $response){
            $this->set_identity('email', $response[ $params['email'] ]);
    	}

/************************************
 * Provider specific private methods
 ************************************/
	
	/**
     * Activate provider specific steps based on features
     * enabled for the provider
     */
    public function activate(){
        # Enable Single Sign-On
        if(in_array("login", $this->features, TRUE)){
            $this->enable_sso();
        }
    }

    /**
	 * Gets the login uri
	 *
	 * @return     string | boolean
	 */
	private function getLoginURI(){
		if($this->environment == 'production') return 'https://login.salesforce.com';
		if($this->environment == 'sandbox') return 'https://test.salesforce.com';
		return FALSE;
	}

}

?>