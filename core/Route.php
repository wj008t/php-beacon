<?php


namespace beacon\core;


class Route
{
    protected string $name;
    protected string $namespace;
    protected string $base;
    protected array $rules = [];

    public function __construct(string $name, string $base = '', string $namespace = '')
    {
        $this->name = $name;
        if (empty($namespace)) {
            $this->namespace = 'app\\' . $name;
        } else {
            $this->namespace = $namespace;
        }
        if (empty($base)) {
            $this->base = '/' . ($name == 'home' ? '' : $name);
        } else {
            $this->base = $base;
        }
        $this->rules['@^/(\w+)/(\w+)$@i'] = ['ctl' => '$1', 'act' => '$2'];
        $this->rules['@^/(\w+)/?$@i'] = ['ctl' => '$1', 'act' => 'index'];
        $this->rules['@^/$@'] = ['ctl' => 'index', 'act' => 'index'];
    }

    /**
     * 设置应用名称
     * @param array $namespace
     */
    public function setNamespace(array $namespace)
    {
        $this->namespace = $namespace;
    }

    /**
     * 添加路由规则
     * @param string $pattern
     * @param array $map
     */
    public function addRule(string $pattern, array $map)
    {
        $this->rules[$pattern] = $map;
    }

    /**
     * 获取引用名称
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * 获取相对目录
     * @return string
     */
    public function getBase(): string
    {
        return $this->base;
    }

    /**
     * 获取应用空间目录
     * @return string
     */
    public function getNamespace(): string
    {
        return $this->namespace;
    }

    /**
     * 获取规则解析
     * @return array
     */
    public function getRules(): array
    {
        return $this->rules;
    }

    /**
     * 泛解析url
     * @param string $ctl
     * @param string $act
     * @param array $keys
     * @return string
     */
    public function resolve(string $ctl, string $act, array $keys): string
    {
        $url = '/{ctl}';
        if (!empty($act) && $act != 'index') {
            $url .= '/{act}';
        }
        return $url;
    }

    /**
     * 返回相对路径
     * @param string $url
     * @return string|null
     */
    public function getURI(string $url): ?string
    {
        $base = rtrim($this->base, '/');
        $pattern = '@^' . preg_quote($base, '@') . '(/.*)?$@i';
        if (preg_match($pattern, $url, $m)) {
            $uri = empty($m[1]) ? '' : $m[1];
            $uri = preg_replace('@^/index\.php@i', '/', $uri);
            if (empty($uri)) {
                $uri = '/';
            }
            return $uri;
        }
        return null;
    }

    /**
     * 返回隐射表
     * @param string $ctl
     * @param string $act
     * @return array
     */
    public function getMap(string $ctl, string $act): array
    {
        $ctl = Util::toCamel($ctl);
        return [
            'namespace' => $this->namespace,
            'classFullName' => $this->namespace . '\\controller\\' . $ctl,
            'className' => $ctl,
            'method' => $act
        ];
    }

}