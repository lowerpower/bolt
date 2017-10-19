<?php

/**
 * @package    calcinai/bolt
 * @author     Michael Calcinai <michael@calcin.ai>

    // updated for "react/socket": "^0.8"
    // Client uses standard reactphp connector interface now
    // https://github.com/lowerpower/bolt

*/

namespace Calcinai\Bolt;

use Calcinai\Bolt\HTTP\Request;
use Calcinai\Bolt\Protocol\ProtocolInterface;
use Calcinai\Bolt\Protocol\RFC6455;
use React\Socket\ConnectionInterface;
use Evenement\EventEmitter;
//use React\Dns\Resolver\Resolver;
use React\EventLoop\LoopInterface;
//use React\Stream\DuplexStreamInterface;


/**
 * The `Client` class is the main class in this package that implements the
 * `ConnectorInterface` and allows you to create websocket client connections.
 *
 * You can use this Client nterface to create ws:// and wss:// websocket streams.
 *
 */
class Client extends EventEmitter {

    /**
     * @var LoopInterface
     */
    private $loop;

    /**
     * @var Resolver
     */
    private $resolver;

    private $options;

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
    const STATE_ERROR       = 'error';

    public function __construct(LoopInterface $loop, array $options = array() )
    {
        $this->protocol = RFC6455::class;
        $this->loop = $loop;
        $this->state = self::STATE_CLOSED;
        $this->heartbeat_interval = null;
        $this->options=$options;
        $this->debug=1;
    }

    public function connect($uri) {
        $this->emit('zoom',array('ccommenctrap'));
        //
        // Validate URL
        //
        if(false === filter_var($uri, FILTER_VALIDATE_URL)){
            throw new \InvalidArgumentException(sprintf('Invalid URI [%s]. Must be in format ws(s)://host:port/path', $uri));
        }
        // parse it
        $this->uri = (object) parse_url($uri);
        //
        // we use socket connector here and specify the options, the options can be TCP and TLS options
        //

        $connector = new \React\Socket\Connector($this->loop,$this->options);

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
        },
        function ($error) {
            // failed to connect due to $error
            $this->setState(self::STATE_ERROR);
            $this->emit('error',array($error->getMessage()) );
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
            case self::STATE_ERROR:
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
