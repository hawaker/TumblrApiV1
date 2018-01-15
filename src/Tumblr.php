<?php
namespace tumblrapi;

use Exception;
use GuzzleHttp\Client;

class Tumblr {

    protected $name = '';
    protected $url = "https://{name}.tumblr.com/api/read/json";
    public $client = null;
    public $response = null;
    public $result;
    public $param = [
        "start" => 0,
        "num" => 20
    ];
    public $object;
    protected $clientParam = [
        'verify' => false,
        'headers' => [
            'User-Agent' => 'Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/63.0.3239.84 Safari/537.36']
    ];
    protected $exceptionHandler = null;
    protected $messageHandler = [];
    protected $loop = true;
    public $userinfo = [];
    public $posts = [];
    public $postsTotal;

    public function __construct($name = '') {
        $this->setName($name);
    }

    public function setName($name) {
        if ($name) {
            $this->name = $name;
            return $this;
        }
    }

    public function setStart(int $start) {
        if ($start < 0) {
            throw new Exception("number {$start} is not allow,the number must bigger then 0");
        }
        $this->param['start'] = $start;
        return $this;
    }

    public function setDebug() {
        $this->param['debug'] = "1";
        return $this;
    }

    public function setId($id) {
        $this->param['id'] = $id;
        return $this;
    }

    public function setType($type) {
        $this->param['type'] = $type;
        return $this;
    }

    public function setFilter($filter) {
        $this->param['filter'] = $filter;
        return $this;
    }

    public function setTagged($tagged) {
        $this->param['tagged'] = $tagged;
        return $this;
    }

    public function setNum(int $num) {
        if ($num < 0 || $num > 50) {
            throw new Exception("number {$num} is not allow,the number must between 0 and 50");
        }
        $this->param['num'] = $num;
    }

    public function getUserinfo() {
        $this->setStart(0);
        $this->setNum(0);
        return $this->fetch();
    }

    public function chunkByCallable(callable $function, int $perPage = 50, int $startNum = 1) {
        $this->setNum($perPage);
        while (True) {
            $this->setStart($startNum);
            $this->fetch();
            $result = $this->toArray();
            if (count($result['posts']) == 0) {
                break;
            }
            $function($result, $startNum);
            $startNum += $perPage;
        }
    }

    public function chunkByMessageHandler(MessageHandler $messageHandler, int $perPage = 50, int $startNum = 1) {
        $this->setNum($perPage);
        while ($this->loop) {
            $this->setStart($startNum);
            $this->fetch();
            if ($this->postsTotal < $startNum) {
                break;
            }
            $messageHandler->handler($this->result);
            $startNum += $perPage;
        }
    }

    public function setMessageHandler($handler) {
        $this->messageHandler[] = $handler;
        return $this;
    }

    public function handle() {
        if (count($this->messageHandler) == 0) {
            //throw new Exception();
        }
        foreach ($this->messageHandler as $handler) {
            if (is_string($handler) && class_exists($handler)) {
                $handler = new $handler();
            }
            if (is_object($handler) && $handler instanceof MessageHandler) {
                $this->exceptionHandler = $handler;
                $this->chunkByMessageHandler($handler);
                continue;
            }
            if (is_callable($handler)) {
                $this->chunkByCallable($handler);
                continue;
            }
            //todo throw Exception
        }
    }

    public function toArray() {
        $result = substr($this->result, 22, -2);
        $this->result = json_decode($result, 1);
    }

    protected function fetch() {
        try {
            if (null == $this->client) {
                $this->client = new Client();
            }
            $this->response = $this->client->get($this->getUrl(), $this->clientParam);
            $this->result = $this->response->getBody()->getContents();
            $this->toArray();
            $this->userinfo = $this->result['tumblelog'];
            $this->posts = $this->result['posts'];
            if (!$this->postsTotal) {
                $this->postsTotal = $this->result['posts-total'];
            }
        } catch (\Exception $e) {
            if ($this->exceptionHandler == null) {
                throw $e;
            }
            if (is_callable($this->exceptionHandler)) {
                $this->exceptionHandler($e);
            } elseif ($this->exceptionHandler instanceof MessageHandler) {
                $this->exceptionHandler->onException($e, $this->param['start']);
            } else {
                throw $e;
            }
        }
    }

    protected function getUrl() {
        $param = [];
        foreach ($this->param as $key => $value) {
            $param[] = "{$key}=$value";
        }
        return str_replace("{name}", $this->name, $this->url) . "?" . implode("&", $param);
    }

    public function setExceptionHandler($handler) {
        $this->exceptionHandler = $handler;
        return $this;
    }

    public function startLoop() {
        $this->loop = true;
        return $this;
    }

    public function endLoop() {
        $this->loop = false;
        return $this;
    }

    public static function test() {
        $tumblr = new static("siwarenqi");
        $tumblr->setMessageHandler(ImageHandler::class)->handle();

        /* $tumblr->chunk(function($result, $page) {
          var_dump($result);
          echo "<hr />";
          echo "page:{$page}";
          echo "<hr />";
          }, 50, 1); */
        //$tumblr->setNum(50);
        //return $tumblr->getUrl();
        //return $tumblr->getUrl();
        //return TopClientUtils::UatmFavoritesItem(15371877);
    }

}
