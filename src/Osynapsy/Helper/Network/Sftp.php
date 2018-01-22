<?php
namespace Osynapsy\Helper\Network;

class Sftp
{
    private $username;
    private $password;
    private $host;
    private $port;
    private $session;
    private $connected=false;
    private $error = array();
    
    public function __construct($username, $password, $host, $port=22)
    {
        $this->username = $username;
        $this->password = $password;
        $this->host = $host;
        $this->port = $port;
    }
    
    public function connect()
    {
        $this->session = ssh2_connect($this->host, $this->port);
        if (ssh2_auth_password($this->session, $this->username, $this->password)) {
            $this->connected=true;
            return true;
        } 
        $this->error[] = 'Authentication Failed...';
        return false;
    }
    
    public function put($local, $remote, $mode=0644)
    {
        if (!$this->connected) {
            $this->error[] = "Not connected. File don't send.";
        }
        if (ssh2_scp_send($this->session, $local, $remote, $mode)) {
            return true;
        }
        return false;
    }
    
    public function get($remote, $local, $mode=0644)
    {
        if (!$this->connected) {
            $this->error[] = "Not connected. File don't send.";
        }
        if (ssh2_scp_recv($this->session, $remote, $local, $mode)) {
            return true;
        }
        return false;
    }
}