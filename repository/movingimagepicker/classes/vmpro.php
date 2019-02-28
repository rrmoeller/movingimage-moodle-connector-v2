<?php

/**
 * VideoManagerPro class
 *
 * @package    repository_movingimagepicker v2
 * @copyright  2019 Rainer Möller
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class VideoManagerPro
{

  const publicApiPath = 'https://api.video-cdn.net/v1/vms/';
  const privateApiPath = 'https://vmpro.movingimage.com/vam/rest/vms/';

  protected $VideoManagerID = 0;
  protected $AccessToken = '';
  protected $RefreshToken = '';

	public function getAccessToken()	{
	  return $this->AccessToken;
	}

	public function setAccessToken($vmproid, $token)	{
	  $this->VideoManagerID = $vmproid;
	  $this->AccessToken = $token;
	}

	public function tryAccessToken($vmproid, $token)	{
    $this->setAccessToken($vmproid, $token);
	  $result = $this->callPublicAPI('');
	  if (strpos($result, '<title>Error</title>') === false) {
      return true;
    } else {
      $this->setAccessToken($vmproid,'');
      return false;
    }
	}

	public function getVideoToken($videoID, $expTime)	{
	  $data = sprintf('{"video-id":"%s", "exp-time": %s}' , $videoID, $expTime);
	  $hash = hash_hmac ( 'sha256', $data , hex2bin($this->SharedSecret) );
	  $token = sprintf ('%s~%s', $expTime , $hash);
	  return $token;
	}

  protected function callAPI($apiPath, $data, $mode, $header)
  {
    $request = curl_init();

		if ($mode == CURLOPT_HTTPGET)
			$url = $apiPath.'?'.http_build_query($data,'', '&');
		else  {
			$url = $apiPath;
			curl_setopt($request, CURLOPT_POSTFIELDS, json_encode($data));
		}

    curl_setopt($request, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($request, CURLOPT_RETURNTRANSFER, TRUE);
    switch ($mode) {
      case 'CURLOPT_PUT':
        curl_setopt($request, CURLOPT_CUSTOMREQUEST, 'PUT');
        break;
      case 'CURLOPT_PATCH':
        curl_setopt($request, CURLOPT_CUSTOMREQUEST, 'PATCH');
        break;
      default:
        curl_setopt($request, $mode, TRUE);
    }
    curl_setopt($request, CURLOPT_URL, $url);
    if ($header)
		  curl_setopt($request, CURLOPT_HEADER, TRUE);

    if ($this->AccessToken !== null) {
		  curl_setopt($request, CURLOPT_HTTPHEADER, array('Content-Type: application/json','Authorization: Bearer '.$this->AccessToken));
    } else
      curl_setopt($request, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));

    $result = curl_exec($request);
    curl_close($request);
    return $result;
  }

  protected function callPublicAPI($apiPath, $data = [], $mode = CURLOPT_HTTPGET, $header = false)
  {
    return $this->callAPI(self::publicApiPath.$apiPath, $data, $mode, $header);
  }

  protected function callPrivateAPI($apiPath, $data = [], $mode = CURLOPT_HTTPGET, $header = false)
  {
    return $this->callAPI(self::privateApiPath.$apiPath, $data, $mode, $header);
  }

  public function login($username, $password, $vmproID = 0)
  {
    $data = ['username' => $username, 'password' => $password];
    $result = $this->callPublicAPI('auth/login', $data, CURLOPT_POST);
    $result = json_decode($result, true);

    if (is_array($result) && !isset($result['message'])) {
      $this->AccessToken = $result['accessToken'];
      $this->RefreshToken = $result['refreshToken'];
      $this->VideoManagerID = $vmproID;
			return true;
    }
    return false;
  }

  private function findChannel ($tree, $channel, $level = 0) {
    if (intval($channel) > 0) {
      if ($tree['id'] == $channel)
        return $tree;
    } else {
      if ($tree['name'] == $channel || $channel == '0')
        return $tree;
    }
    if (count($tree['children']) > 0) {
      foreach ($tree['children'] as $child) {
        $success = $this->findChannel($child, $channel, $level + 1);
        if ($success != [])
          return $success;
      }
    }
    return [];
  }

  public function getChannels($baseChannel = '0')
  {
    $result = $this->callPublicAPI($this->VideoManagerID.'/channels');
    $fullTree = json_decode($result, true);
    if (!(is_array($fullTree) && isset($fullTree["id"])))
      return [];

		if ($baseChannel != '0' && $baseChannel != null)
      $list = $this->findChannel($fullTree, $baseChannel);
    else
      $list = $fullTree;

		return $list;
  }

  public function getRootChannelID()
  {
    $result = $this->callPublicAPI($this->VideoManagerID.'/channels');
    $fullTree = json_decode($result, true);
    if (is_array($fullTree) && isset($fullTree["id"]))
		  return $fullTree['id'];
    else
      return 0;
  }

  public function getChannelIDByName($name)
  {
    $channels = $this->getChannels($name);
    if (!is_array($channels) || $channels == [] || !isset($channels['id']))
      return 0;
    else
      return $channels['id'];
  }

  public function createChannel($name,$parentID,$ownerGroupID = 0)
  {
    $data = array();
    $data['name'] =  $name;
    $data['parentId'] =  $parentID;
    if ($ownerGroupID != 0)
      $data['ownerGroupId'] =  $ownerGroupID;
    $result = $this->callPublicAPI($this->VideoManagerID.'/channels',$data, CURLOPT_POST, true);
		preg_match('|Location: *'.self::publicApiPath.'[0-9]*/channels/([0-9]*)|',$result,$match);

    if (is_array($match) && isset($match[1]))
			$list = $match[1];
		else
			$list = 0;
		return $list;
  }

  public function addVideoToChannel($channelID, $videoID)
  {
    $result = $this->callPublicAPI($this->VideoManagerID.'/channels/'.$channelID.'/videos/'.$videoID, [], CURLOPT_POST);
    return json_decode($result, true);
  }

  public function getVideos($channel = 0, $limit = 50, $offset = 0, $search = '', $sort = '', $sub_channels = false, $public = '', $sortasc = false, $channel_assignments = false)
  {
		$data = array();
  	if ($channel !== 0)
  		$data['channel_id'] = $channel;
  	$data['offset'] = $offset;
    $data['limit'] = $limit;
    $data['include_sub_channels'] = $sub_channels;
    $data['include_custom_metadata'] = 'true';
    $data['include_keywords'] = 'true';
  	if ($sortasc)
  		$data['order'] = 'asc';
    if ($channel_assignments)
  		$data['include_channel_assignments'] = 'true';
  	if ($search != '')
  		$data['search_term'] = $search;
  	if ($public != '')
  		$data['publication_state'] = $public;
  	if ($sort != '')
  		$data['order_property'] = $sort;

    $result = $this->callPublicAPI($this->VideoManagerID.'/videos', $data);
    $list = json_decode($result, true);

  	return $list;
  }

  public function getVideo($videoID,$data)
  {
    $result = $this->callPublicAPI($this->VideoManagerID.'/videos/'.$videoID, $data);
    return json_decode($result, true);
  }

  public function getCustomMetadata($videoID)
  {
    $result = $this->callPublicAPI($this->VideoManagerID.'/videos/'.$videoID.'/metadata');
    return json_decode($result, true);
  }

  public function setCustomMetadata($videoID, $data)
  {
    $result = $this->callPublicAPI($this->VideoManagerID.'/videos/'.$videoID.'/metadata',$data, 'CURLOPT_PATCH');
    return json_decode($result, true);
  }

  public function createVideoEntity($filename, $title ='', $description = '', $keywords = array(), $channelID = 0, $groupID = 0, $autopublish = true)
  {
		$data = array();
		$data['fileName'] = $filename;
		$data['autoPublish'] = $autopublish;
		if ($title != '')
			$data['title'] = $title;
		if ($description != '')
			$data['description'] = $description;
		if (is_array($keywords) && count($keywords) > 0)
			$data['keywords'] = $keywords;
		else if (is_string($keywords) && $keywords != '')
			$data['keywords'] = explode(',',$keywords);
		if ($channelID != 0)
			$data['channel'] = $channelID;
		if ($groupID != 0)
			$data['group'] = $groupID;
    $result = $this->callPublicAPI($this->VideoManagerID.'/videos', $data, CURLOPT_POST, true);
		preg_match('|Location: *'.self::publicApiPath.'[0-9]*/videos/([^[:space:]]*)|',$result,$match);
		if (is_array($match) && isset($match[1]))
			$list = ['id' => $match[1] ];
		else
			$list = $result;

		return $list;
  }

  public function getUploadURL($videoID)
  {
    $result = $this->callPublicAPI($this->VideoManagerID.'/videos/'.$videoID.'/url',[], CURLOPT_HTTPGET, true);
    preg_match('|Location: *(http[^[:space:]]*)|',$result,$match);
		if (is_array($match) && isset($match[1]))
			$list = ['upload_url' => $match[1] ];
		else
			$list = [];

    return $list;
  }

  public function getUsers()
  {
      $result = $this->callPrivateAPI($this->VideoManagerID.'/users');
      $list = json_decode($result, true);
      $keyList = array();
      foreach ($list as $e)
        if (isset($e["id"]))
          $keyList[$e["id"]] = $e;
      return $keyList;
  }

  public function getUserIDByEmail($email)
  {
      $users = $this->getUsers();
      if (is_array($users))
        foreach ($users as $user) {
          if (isset($user['email']) && $user['email'] == $email)
            return $user['id'];
        }
	    return 0;
  }

  public function createUser($email, $sendActivationLink = true, $emailVerified = false, $enabled = false, $companyRoleID,
                             $locale = ['id' => 1, 'language' => 'de', 'country' => 'DE', 'languageTag' => 'de_DE'])
  {
      $data = [
        'companyName' => '',
        'email' => $email,
        'emailVerified' => $emailVerified,
        'enabled' => $enabled,
        'firstName' => '',
        'lastName' => '',
        'locale' => $locale,
        'loginName' => '',
        'sendActivationLink' => $sendActivationLink,
        'telephone' => ''
      ];
      $result = $this->callPrivateAPI($this->VideoManagerID.'/users', $data, CURLOPT_POST, true);
      preg_match('|Location: *'.self::privateApiPath.'[0-9]*/users/([0-9]*)|',$result,$match);
  		if (is_array($match) && isset($match[1])) {
        $this->addUserToGroup($match[1],$this->getDefaultGroup(),$companyRoleID);
  			$list = $match[1];
  		} else
  			$list = false;
	    return $list;
  }

  public function addUserToGroup($userID, $groupID, $userroleID)
  {
    $data = [
      'groupId' => $groupID,
      'roleId' => $userroleID,
      'userId' => $userID
    ];
    $result = $this->callPrivateAPI($this->VideoManagerID.'/group-associations', $data, CURLOPT_POST);
    $list = json_decode($result, true);
    return $list;
  }

  public function createGroup($name, $description = '')
  {
      $data = array();
      $data['name'] = $name;
      $data['description'] = $description;
      $result = $this->callPrivateAPI($this->VideoManagerID.'/groups',$data,CURLOPT_POST, true);

  		preg_match('|Location: *'.self::privateApiPath.'[0-9]*/groups/([0-9]*)|',$result,$match);
  		if (is_array($match) && isset($match[1]))
  			$list = $match[1];
  		else
  			$list = false;
	    return $list;
  }

    public function getGroups()
    {
        $result = $this->callPrivateAPI($this->VideoManagerID.'/groups');
        $list = json_decode($result, true);
        $keyList = array();
        if (is_array($list))
          foreach ($list as $e)
            if (isset($e["id"]))
      			   $keyList[$e["id"]] = $e;
    		return $keyList;
    }

    public function getRoles()
    {
        $result = $this->callPrivateAPI($this->VideoManagerID.'/roles');
        $list = json_decode($result, true);
        $keyList = array();
        if (is_array($list))
          foreach ($list as $e)
            if (isset($e["id"]))
        			$keyList[$e["id"]] = $e;
    		return $keyList;
    }

    public function getGroupIDByName($name)
    {
        $groups = $this->getGroups();
        if (is_array($groups))
          foreach ($groups as $group) {
            if (isset($group['name']) && $group['name'] == $name)
              return $group['id'];
          }
  	    return 0;
    }

    public function getDefaultGroup()
    {
      $groups = $this->getGroups();
      if (is_array($groups))
        foreach ($groups as $group) {
          if (isset($group['defaultGroup']) && $group['defaultGroup'] === true)
            return $group['id'];
        }
      return 0;
    }

    public function getGroupsAndUsers()
    {
        $result = $this->callPrivateAPI($this->VideoManagerID.'/group-associations');
        $list = json_decode($result, true);
		    return $list;
    }

    public function getAllSAMLGroupsAndRoles()
    {
      $result = $this->callPrivateAPI($this->VideoManagerID.'/saml-ownership-mapping');
      $list = json_decode($result, true);
      $keyList = array();
      if (is_array($list))
        foreach ($list as $e)
          if (isset($e["id"]))
            $keyList[$e["id"]] = $e;
      return $keyList;
    }

    public function getSAMLRolesForGroup($groupName)
    {
      $assignments = $this->getAllSAMLGroupsAndRoles();
      $list = [];

      if (is_array($assignment))
        foreach ($assignment as $a)
          if (isset($a["groupName"]) && $a["groupName"] == $groupName)
            $list[$a["samlAttribute"]] = $a["roleName"];
      return $list;
    }

    public function createSAMLGroupAndRole($samlAttribute,$groupID,$roleID)
    {
        $data = array();
        $data['samlAttribute'] = $samlAttribute;
        $data['groupId'] = $groupID;
        $data['roleId'] = $roleID;
        $result = $this->callPrivateAPI($this->VideoManagerID.'/saml-ownership-mapping',$data,CURLOPT_POST, true);

    		preg_match('|Location: *'.self::privateApiPath.'[0-9]*/saml-ownership-mapping/([0-9]*)|',$result,$match);
    		if (is_array($match) && isset($match[1]))
    			$list = $match[1];
    		else
    			$list = false;
  	    return $list;
    }

    public function setVideoData($videoID, $data)
    {
      $result = $this->callPublicAPI($this->VideoManagerID.'/videos/'.$videoID, $data, 'CURLOPT_PATCH');
      return json_decode($result, true);
    }

    public function setVideoDeletionTimer($videoID, $timeStamp)
    {
      $data = ['scheduledTrashDate' => $timeStamp];
      $result = $this->callPrivateAPI($this->VideoManagerID.'/videos/'.$videoID, $data, 'CURLOPT_PATCH');
      return json_decode($result, true);
    }

    public function getPlayers()
    {
      $result = $this->callPublicAPI($this->VideoManagerID.'/players');
      $list = json_decode($result, true);
      $keyList = array();
  		foreach ($list as $e)
        if (isset($e["id"]))
    			$keyList[$e["id"]] = $e;
  		return $keyList;
    }

    public function getSecurityPolicies()
    {
      $result = $this->callPrivateAPI($this->VideoManagerID.'/security-policies');
      $list = json_decode($result, true);
      $keyList = array();
      foreach ($list as $e)
        if (isset($e["id"]))
          $keyList[$e["id"]] = $e;
      return $keyList;
    }

    public function getCustomMetadataFields()
    {
      $data = ['entityType' => 'VIDEO'];
      $result = $this->callPrivateAPI($this->VideoManagerID.'/custom-metadata-fields',$data);
      $list = json_decode($result, true);
      $keyList = array();
      foreach ($list as $e)
        if (isset($e["id"]))
          $keyList[$e["id"]] = $e;
      return $keyList;
    }

}
