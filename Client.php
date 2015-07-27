<?php
/**
 * PHP wrapper for the Dribbble API.
 * 
 * @author   Martin Bean <martin@martinbean.co.uk>
 * @license  MIT License
 * @version  3.0
 */
 
 /**
 * Updated by Evan D'Elia for Dribble API v1
 * www.pixelpusher.ninja
 */

/**
 * The core Dribbble API PHP wrapper class.
 */
class Client
{
    /**
     * Default options for cURL requests.
     *
     * @var array
     */
    public static $CURL_OPTS = array(
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 60,
        CURLOPT_USERAGENT      => 'dribbble-api-php-wrapper'
    );
    
    /**
     * The API endpoint.
     *
     * @var string
     */
    protected $endpoint = 'https://api.dribbble.com/v1';
    
    /**
     * Returns details for a shot specified by ID.
     *
     * @param  integer $id
     * @return object
     */
    public function getShot($id)
    {
        return $this->makeRequest(sprintf('/shots/%d', $id), 'GET');
    }
    
    /**
     * Returns the set of rebounds (shots in response to a shot) for the shot specified by ID.
     *
     * @param  integer $id
     * @return object
     */
    public function getShotRebounds($id)
    {
        return $this->makeRequest(sprintf('/shots/%d/rebounds', $id), 'GET');
    }
    
    /**
     * Returns the set of comments for the shot specified by ID.
     *
     * @param  integer $id
     * @param  integer $page
     * @param  integer $per_page
     * @return object
     */
    public function getShotComments($id, $page = 1, $per_page = 15)
    {
        $options = array(
            'page' => intval($page),
            'per_page' => intval($per_page)
        );
        return $this->makeRequest(sprintf('/shots/%d/comments', $id), 'GET', $options);
    }
    
    /**
     * Returns the specified list of shots.
     *
     * @param  string  $list
     * @param  integer $page
     * @param  integer $per_page
     * @return object
     */
    public function getShotsList($list = 'everyone', $page = 1, $per_page = 15)
    {
        return $this->makeRequest('/shots', 'GET');
    }
    
    /**
     * Returns the most recent shots for the player specified.
     *
     * @param  mixed   $id
     * @param  integer $page
     * @param  integer $per_page
     * @return object
     */
    public function getPlayerShots($id, $page = 1, $per_page = 15)
    {
        $options = array(
            'page' => intval($page),
            'per_page' => intval($per_page)
        );
        return $this->makeRequest(sprintf('/players/%s/shots', $id), 'GET', $options);
    }
    
    /**
     * Returns the most recent shots published by those the player specified is following.
     *
     * @param  mixed   $id
     * @param  integer $page
     * @param  integer $per_page
     * @return object
     */
    public function getPlayerFollowingShots($id, $page = 1, $per_page = 15)
    {
        $options = array(
            'page' => intval($page),
            'per_page' => intval($per_page)
        );
        return $this->makeRequest(sprintf('/players/%s/shots/following', $id), 'GET', $options);
    }
    
    /**
     * Returns shots liked by the player specified.
     *
     * @param  mixed   $id
     * @param  integer $page
     * @param  integer $per_page
     * @return object
     */
    public function getPlayerLikes($id, $page = 1, $per_page = 15)
    {
        $options = array(
            'page' => intval($page),
            'per_page' => intval($per_page)
        );
        return $this->makeRequest(sprintf('/players/%s/shots/likes', $id), 'GET', $options);
    }
    
    /**
     * Returns profile details for a player specified.
     *
     * @param  mixed   $id
     * @return object
     */
    public function getPlayer($id)
    {
        return $this->makeRequest(sprintf('/players/%s', $id), 'GET');
    }
    
    /**
     * Returns the list of followers for a player specified.
     *
     * @param  mixed  $id
     * @return object
     */
    public function getPlayerFollowers($id)
    {
        return $this->makeRequest(sprintf('/players/%s/followers', $id), 'GET');
    }
    
    /**
     * Returns the list of players followed by the player specified.
     *
     * @param  mixed  $id
     * @return object
     */
    public function getPlayerFollowing($id)
    {
        return $this->makeRequest(sprintf('/players/%s/following', $id), 'GET');
    }
    
    /**
     * Returns the list of players drafted by the player specified.
     *
     * @param  mixed  $id
     * @return object
     */
   public function getPlayerDraftees($id, $page = 1, $per_page = 15)
    {
        $options = array(
            'page' => intval($page),
            'per_page' => intval($per_page)
        );
        return $this->makeRequest(sprintf('/players/%s/draftees', $id), 'GET', $options);
    }
    
    /**
     * Makes a HTTP request.
     * This method can be overriden by extending classes if required.
     *
     * @param  string $url
     * @param  string $method
     * @param  array  $params
     * @return object
     * @throws Exception
     */
    protected function makeRequest($url, $method = 'GET', $params = array())
    {
    
    	$params = array(
            'page' => 1,
            'per_page' => 100
            //'callback' => 'bar'
        );
    	//$cmd = "curl -H 'Authorization: Bearer 21771eb391e1503e4d282eaea150601e6d0e97e3ac923c87f1956dc34977a9b4' 'https://api.dribbble.com/v1/shots/'"
        $ch = curl_init();
        if (!empty($params)) {
            curl_setopt($ch, CURLOPT_URL, $this->endpoint . $url . '?' . http_build_query($params, null, '&'));
        }
        else{
        	curl_setopt($ch, CURLOPT_URL, $this->endpoint . $url);
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer 21771eb391e1503e4d282eaea150601e6d0e97e3ac923c87f1956dc34977a9b4"]);
        
        $result = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($result === false) {
            throw new Exception(curl_error($ch), curl_errno($ch));
        }
        
        //echo $result;
        
        $result = json_decode($result);
        if (isset($result->message)) {
            throw new Exception($result->message, $status);
        }
        return $result;
    }
}
