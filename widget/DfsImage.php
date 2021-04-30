<?php


namespace beacon\widget;

#[\Attribute]
class DfsImage extends DfsFile
{
    protected function code(array $attrs = []): string
    {
        $attrs['data-type'] = 'image';
        return parent::code($attrs);
    }
}