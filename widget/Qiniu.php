<?php
/**
 * Created by PhpStorm.
 * User: wj008
 * Date: 2017/12/14
 * Time: 18:02
 */

namespace  beacon\widget;


use beacon\Config;
use beacon\Field;
use Qiniu\Auth;

class Qiniu extends Hidden
{

    public function code(Field $field, $args)
    {
        $args['yee-module'] = 'qiniu';
        $bucket = Config::get('qiniu.bucket');
        $accessKey = Config::get('qiniu.access_key');
        $secretKey = Config::get('qiniu.secret_key');
        $returnBody = '{"key":"$(key)","hash":"$(etag)","fsize":$(fsize),"name":"$(x:name)"}';
        $policy = array(
            'returnBody' => $returnBody
        );
        $auth = new Auth($accessKey, $secretKey);
        $upToken = $auth->uploadToken($bucket, null, 3600, $policy);
        $args['data-token'] = $upToken;
        $args['data-domain'] = Config::get('qiniu.domain', 'http://rwxf.qiniudn.com/');
        $field->explodeAttr($attr, $args);
        $field->explodeData($attr, $args);
        return '<input ' . join(' ', $attr) . ' />';
    }

    public function assign(Field $field, array $data)
    {
        $field->varType = 'string';
        return parent::assign($field, $data);
    }
}