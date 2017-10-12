<?php

/**
 * @package    calcinai/bolt
 * @author     Michael Calcinai <michael@calcin.ai>

    // updated for "react/socket": "^0.8"
    // https://github.com/lowerpower

*/

namespace Calcinai\Bolt;

use Calcinai\Bolt\HTTP\Request;
use Calcinai\Bolt\Protocol\ProtocolInterface;
use Calcinai\Bolt\Protocol\RFC6455;
use React\Socket\ConnectionInterface;
use Evenement\EventEmitter;
use React\Dns\Resolver\Resolver;
use React\EventLoop\LoopInterface;
use React\Stream\DuplexStreamInterface;


class Client extends EventEmitter {

    /**
     * @var LoopInterface
     */
    private $loop;

    /**
     * @var Resolver
     */
    private $resolver;

    private $options=array();
    private $context=array();

    /**
     * The uri of the conenction
     *
     * StdClass with parameters from parse_url
     *
     * @var object
     */
    private $uri;

    /**
     * The protocol class to use
     *
     * @var string
     */
    private $protocol;

    /**
     * @var Protocol\AbstractProtocol
     */
    private $transport;

    private $heartbeat_interval;

    private $state;

    private $debug;

    const PORT_DEFAULT_HTTP  = 80;
    const PORT_DEFAULT_HTTPS = 443;

    const STATE_CONNECTING  = 'connecting';
    const STATE_CONNECTED   = 'connected';
    const STATE_CLOSING     = 'closing';
    const STATE_CLOSED      = 'closed';

/*    
	$options:

	'tcp' => $tcp,
    'tls' => $tls,
    'unix' => $unix,

    'dns' => false,
    'timeout' => false,
*/

    public function __construct($uri, LoopInterface $loop, $options=array(), $context=array(), $protocol = null){

        if(false === filter_var($uri, FILTER_VALIDATE_URL)){
            throw new \InvalidArgumentException(sprintf('Invalid URI [%s]. Must be in format ws(s)://host:port/path', $uri));
        }

        if($protocol !== null) {
            if(!in_array(ProtocolInterface::class, class_implements($protocol))){
                throw new \InvalidArgumentException(sprintf('%s must implement %s', $protocol, ProtocolInterface::class));
            }
            $this->protocol = $protocol;
        } else{

            echo "RFC6455\n";
            $this->protocol = RFC6455::class;
        }

        $this->uri = (object) parse_url($uri);
        $this->loop = $loop;
        //$this->resolver = $resolver;
        $this->state = self::STATE_CLOSED;
        $this->heartbeat_interval = null;
        $this->options=$options;
        $this->context=$context;
        $this->debug=1;
    }

    public function connect() {

        // how do we do this, we need the overloaded Connector
        //$connector = new \React\SocketClient\Connector($this->loop, $this->options);
        $options=array('tcp'=>$this->options,'tls'=>$this->context);

        // we use socket connector here and specify the options, the options can be TCP and TLS options
        //
        $connector = new \React\Socket\Connector($this->loop,$options);

        switch($this->uri->scheme){
            case 'ws':
                $method="tcp://";
                $port = isset($this->uri->port) ? $this->uri->port : self::PORT_DEFAULT_HTTP;
                break;
            case 'wss':
                $method="tls://";
                $port = isset($this->uri->port) ? $this->uri->port : self::PORT_DEFAULT_HTTPS;
                break;
            default:
                throw new \InvalidArgumentException(sprintf('Invalid scheme [%s]', $this->uri->scheme));
        }
        
        $that = $this;

        if($this->debug) echo "calling Connector with ".$method.$this->uri->host.":".$port."\n";
        
        $connector->connect($method.$this->uri->host.":".$port)->then(function (ConnectionInterface $conn) use($that) {

            if($this->debug) echo "Connected, create websocket\n";
            
            $that->transport = new $that->protocol($that, $conn);
            $that->transport->upgrade();
        });

        $this->setState(self::STATE_CONNECTING);
    }

    public function setState($state){
        $this->state = $state;

        switch($state){
            case self::STATE_CONNECTING:
                $this->emit('connecting');
                break;
            case self::STATE_CONNECTED:
                $this->emit('connect');
                break;
            case self::STATE_CLOSING:
                $this->emit('closing');
                break;
            case self::STATE_CLOSED:
                $this->emit('close');
                break;
        }

        $this->emit('stateChange', [$state]);

        return $this;
    }

    public function getState(){
        return $this->state;
    }

    public function getURI(){
        return $this->uri;
    }

    public function getLoop(){
        return $this->loop;
    }

    public function setOrigin($origin) {
        Request::setDefaultHeader('Origin', $origin);
        return $this;
    }

    public function send($string) {
        $this->transport->send($string);
    }

    public function setHeartbeatInterval($interval) {
        $this->heartbeat_interval = $interval;
    }

    public function getHeartbeatInterval() {
        return $this->heartbeat_interval;
    }

    public function getDebugState()
    {
        return $this->debug;
    }

    public function setDebugState($state)
    {
        $this->debug=$state;
        return $this->debug;
    }
}
