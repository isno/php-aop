<?php
if (! defined('ADVICE_PATH'))
{
    define('ADVICE_PATH',  dirname(__FILE__) . DIRECTORY_SEPARATOR.'/advices/');
}


class NoAroundAdviceEventException extends Exception{}
class LazyAdviceException extends Exception{}

interface Advice
{
    function before(AdviceContainer $container);
    function after(AdviceContainer $container);
    function around(AdviceContainer $container);
    function exception(Exception $e);
}


class AdviceContainer
{
    /**
     * @var AdviceContainer $target
     */
    private $target;
    /**
     * @var Advice $advice
     */
    private $advice;

    private $class, $method, $params, $method_reflection;

    private $advice_result = array();

    private $lazy_advices = array();
    private $last_lazy_advices = array();

    private $original_method = true;

    private $ret = null;

    function __construct($target, $advice = null)
    {
        $this->target = $target;
        $this->advice = $advice;
    }

    function addAdvice($advice)
    {
        return new self($this, $advice);
    }

    private function lazyAdvices()
    {
        if (method_exists($this->target, $this->method) && ! $this->target instanceof AdviceContainer)
        {
            if ($this->lazy_advices)
            {
                if ($this->last_lazy_advices != $this->lazy_advices)
                {
                    $target = clone $this->target;
                    $container = new AdviceContainer($target);
                    $this->last_lazy_advices = array_reverse($this->lazy_advices);
                    $this->advice = array_pop($this->lazy_advices);

                    foreach ($this->lazy_advices as $lazy_advice)
                    {
                        $container = $container->addAdvice($lazy_advice);
                    }

                    $this->target = $container;

                    // 重置
                    $this->lazy_advices = array();

                }
                else
                {
                    throw new Exception('bad code, loop advices');
                }
            }
        }
    }

    private function call()
    {
        if (method_exists($this->target, $this->method) && ! $this->target instanceof AdviceContainer)
        {
            if ($this->original_method)
            {
                $this->ret = call_user_func_array(array($this->target, $this->method), $this->getParamValues());
            }
            return $this->ret;
        }
        else
        {
            if ($this->target instanceof AdviceContainer)
            {
                try
                {
                    try
                    {
                        $this->ret = $this->advice->around($this);
                        return $this->ret;
                    }
                    catch(NoAroundAdviceEventException $e)
                    {
                        $this->advice->before($this);
                        $this->ret = $this->proceed();
                        $this->advice->after($this);
                        return $this->ret;
                    }
                }
                catch(LazyAdviceException $e)
                {
                    $this->lazy_advices[] = $this->advice;
                    $this->ret = $this->proceed();
                    return $this->ret;
                }
                catch(Exception $e)
                {
                    return $this->advice->exception($e);
                }
            }
            else
            {
                throw new Exception('method ' . $this->method . ' is not defined');
            }
        }
    }

    function __call($method, $params)
    {
        $this->method = $method;
        list ($this->class, $_params, $this->method_reflection) = $params;
        
        $this->params = array();
        if ($_params)
        {
            /**
             * @var ReflectionParameter $param
             */
            foreach ($params[2] as $k => $param)
            {
                $this->params[$param->getName()] = $_params[$k];
            }
        }

        $this->lazyAdvices();
        return $this->call();
    }

    function proceed()
    {
        if ($this->target instanceof AdviceContainer)
        {
            $this->target->setAdviceResults($this->getAdviceResults());
            $this->target->setOriginalMethod($this->getOriginalMethod());
            $this->target->setLazyAdvices($this->getLazyAdvices());
            $this->target->setLastLazyAdvices($this->last_lazy_advices);
        }
        
        $ret = call_user_func(array($this->target, $this->method), $this->class, $this->getParamValues(), $this->method_reflection);

        if ($this->target instanceof AdviceContainer)
        {
            $this->setAdviceResults($this->target->getAdviceResults());
        }
        
        return $ret;
    }

    function setAdviceResult($key, $val)
    {
        $key = str_replace('.', '', $key);
        $prefix = $this->adviceResultPrefix();
        $this->advice_result[$prefix . $key] = $val;
    }

    function getAdviceResult($key)
    {
        if (false === strpos($key, '.'))
        {
            $key = $this->adviceResultPrefix() . $key;
        }
        return $this->advice_result[$key];
    }

    function getAdviceResults()
    {
        return $this->advice_result;
    }

    function setAdviceResults($advice_result)
    {
        return $this->advice_result = $advice_result;
    }

    function issetAdviceResult($key)
    {
        if (false === strpos($key, '.'))
        {
            $key = $this->adviceResultPrefix() . $key;
        }
        return array_key_exists($key, $this->advice_result);
    }

    private function adviceResultPrefix()
    {
        $backtrace = debug_backtrace();
        return isset($backtrace[2]) ? $backtrace[2]['class'] . '.' : '';
    }

    function setLastLazyAdvices($last_lazy_advices)
    {
        $this->last_lazy_advices = $last_lazy_advices;
    }

    function getMethod()
    {
        return $this->method;
    }

    function getParam($key, $default = null)
    {
        return array_key_exists($key, $this->params) ? $this->params[$key] : $default;
    }

    function getParams()
    {
        return $this->params;
    }

    function getClassName()
    {
        return $this->class;
    }
    
    function setParam($key, $val)
    {
        if (array_key_exists($key, $this->params))
        {
            $param = & $this->params[$key];
            if (gettype($val) == gettype($param) && ! is_object($val))
            {
                if (is_array($val))
                {
                    $keys = array_keys($param);
                    foreach ($val as $k => $v)
                    {
                        if (in_array($k, $keys))
                        {
                            $param[$k] = $v;
                        }
                    }
                }
                else
                {
                    $param = $val;
                }
                return true;
            }
            return false;
        }
        else
        {
            return false;
        }
    }

    function getParamValues()
    {
        return array_values($this->params);
    }

    /**
     * @param Bool $bool
     * @return void
     * 设置original method是否运行开关
     * 与原值与操作
     */
    function setOriginalMethod($bool)
    {
        $this->original_method = $bool && $this->original_method;
    }

    function getOriginalMethod()
    {
        return $this->original_method;
    }

    function getLazyAdvices()
    {
        return $this->lazy_advices;
    }

    function setLazyAdvices($lazy_advices)
    {
        $this->lazy_advices = $lazy_advices;
    }

    function getRet()
    {
        return $this->ret;
    }
}

class AdviceProxy
{
    /**
     * 4 events
     * @var Advice
     */
    private $before, $after, $around, $exception;
    private $method, $params;

    function __construct($event, $advice, $method, $params = array())
    {
        $this->$event = $advice;
        $this->method = $method;
        $this->params = $params;
    }

    function __call($event, $params)
    {
        if ($this->$event)
        {
            $params = array_merge($this->params, $params);
            return call_user_func_array(array($this->$event, $this->method), $params);
        }
        else
        {
            return null;
        }
    }

    function around(AdviceContainer $container)
    {
        if ($this->around)
        {
            $this->params[] = $container;
            return call_user_func_array(array($this->around, $this->method), $this->params);
        }
        else
        {
            throw new NoAroundAdviceEventException();
        }
    }

    function exception(Exception $e)
    {
        if ($this->exception)
        {
            return $this->exception->{$this->method}($e);
        }
        else
        {
            throw $e;
        }
    }
}

class AOPConfig
{
    static function get()
    {
        global $aop_config;
        return array_reverse($aop_config);
    }
}

class AOP
{
    private static $method_reflection = array();
    private static $aop_config = array();
    private $config = array();
    private $call_config = array();

    public static function getInstance($class)
    {
        self::$aop_config = AOPConfig::get();
        return new self($class);
    }

    public function getAdvices($class, $method)
    {
        $aop_config = array_reverse(array_merge(self::$aop_config, $this->config, $this->call_config));
        // reset call_config
        $this->call_config = array();

        $advice_configs = array();
        foreach ($aop_config as $config)
        {
            $point_class = ucwords($config['point']['class']);
            if (( $point_class== $class || $config['point']['class'] == '*') && ($config['point']['method'] == $method || $config['point']['method'] == '*'))
            {
                $advice_configs[] = $config;
            }
        }
        return $advice_configs;
    }

    function __construct($class)
    {
        $this->class = ucwords($class);
    }

    function config($config)
    {
        $this->config[] = $config;
        return $this;
    }

    function unconfig($unconfig)
    {
        foreach (self::$aop_config as $n => $config)
        {
            if ($config == $unconfig)
            {
                unset(self::$aop_config[$n]);
            }
        }
        sort(self::$aop_config);

        foreach ($this->config as $n => $config)
        {
            if ($config == $unconfig)
            {
                unset($this->config[$n]);
            }
        }
        sort($this->config);

        foreach ($this->call_config as $n => $config)
        {
            if (isset($config['advice']['class']))
            {
                $_config = array('class'=>$config['advice']['class'], 'method'=>$config['advice']['method']);
            }
            else if (isset($config['advice']['object']))
            {
                $_config = array('object'=>$config['advice']['class'], 'method'=>$config['advice']['method']);
            }
            if ($_config == $unconfig)
            {
                unset($this->call_config[$n]);
            }
        }
        sort($this->call_config);

        return $this;
    }

    function __call($method, $params)
    {
        $advice_configs = $this->getAdvices($this->class, $method);

        $advice_container = new AdviceContainer(new $this->class());

        foreach ($advice_configs as $config)
        {
            $_params = isset($config['advice']['params']) ? $config['advice']['params'] : array();
            if (isset($config['advice']['class']))
            {
                $advice_file = ADVICE_PATH . $config['advice']['class'] . '.php';
                if (file_exists($advice_file))
                {
                    include_once $advice_file;
                }

                $advice_proxy = new AdviceProxy($config['event'], new $config['advice']['class'], $config['advice']['method'], $_params);
                $advice_container = $advice_container->addAdvice($advice_proxy);
            }
            else if (isset($config['advice']['object']))
            {
                $advice_proxy = new AdviceProxy($config['event'], $config['advice']['object'], $config['advice']['method'], $_params);
                $advice_container = $advice_container->addAdvice($advice_proxy);
            }
        }

        if (! isset(self::$method_reflection[$this->class][$method]))
        {
            $callee = new ReflectionMethod($this->class, $method);
            self::$method_reflection[$this->class][$method] = $callee;
        }
        else
        {
            $callee = self::$method_reflection[$this->class][$method];
        }

        
        $ret = call_user_func(array($advice_container, $method), $this->class, $params, $callee->getParameters());

        return $ret;
    }

    function before($advice)
    {
        return $this->event($advice, 'before');
    }

    function after($advice)
    {
        return $this->event($advice, 'after');
    }

    function around($advice)
    {
        return $this->event($advice, 'around');
    }

    private function event($advice, $event)
    {
        $this->call_config[] = array(
                    'event'	 => $event,
                    'point' => array('class'=>$this->class, 'method'=>'*'),
                    'advice'=> $advice,
        );
        return $this;
    }
}
