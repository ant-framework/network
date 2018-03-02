<?php
namespace Ant\Support;

use ArrayAccess;
use RuntimeException;
use InvalidArgumentException;

/**
 * Class Arr
 * @package Ant\Support
 */
class Arr
{
    /**
     * 检查是否为数组
     *
     * @param mixed $value
     * @return bool
     */
    public static function accessible($value)
    {
        return is_array($value) || $value instanceof ArrayAccess;
    }

    /**
     * 将数组中的数组依次合并
     *
     * @param $array
     * @return array
     */
    public static function collapse($array)
    {
        $results = [];

        foreach ($array as $values) {
            if ($values instanceof Collection) {
                $values = $values->toArray();
            } elseif (! is_array($values)) {
                continue;
            }

            $results = array_merge($results, $values);
        }

        return $results;
    }

    /**
     * 打乱数组
     *
     * @param array $array
     * @return array
     */
    public static function shuffle(array $array)
    {
        shuffle($array);

        return $array;
    }

    /**
     * 将多维数组进行降维
     *
     * @param array|Collection $array   进行降为的集
     * @param int $depth                递归深度
     * @return mixed
     */
    public static function flatten($array, $depth = INF)
    {
        $array = $array instanceof Collection ? $array->toArray() : $array;

        return array_reduce($array, function ($result, $item) use ($depth) {
            $item = $item instanceof Collection ? $item->toArray() : $item;

            if (! is_array($item)) {
                return array_merge($result, [$item]);
            } elseif ($depth === 1) {
                return array_merge($result, array_values($item));
            } else {
                return array_merge($result, static::flatten($item, --$depth));
            }
        }, []);
    }

    /**
     * 剔除数组中的空值,false不算空值
     *
     * @param array $array
     * @return array
     */
    public static function removalEmpty(array &$array)
    {
        if (is_array($array)) {
            foreach ($array as $key => $value) {
                if ($value == '' && !is_bool($value)) {
                    static::forget($array, $key);
                }
            }
        }
    }

    /**
     * 检查key是否存在在输入参数中
     *
     * @param array|ArrayAccess $array
     * @param mixed $key
     * @return bool
     */
    public static function exists($array, $key)
    {
        if (!static::accessible($array)) {
            return false;
        }

        return $array instanceof ArrayAccess
            ? $array->offsetExists($key)
            : array_key_exists($key,$array);
    }

    /**
     * 从多维数组中获取值
     *
     * @param $array
     * @param $path
     * @param null $default
     * @return mixed
     */
    public static function get($array, $path, $default = null)
    {
        if (!is_string($path) || empty($path)) {
            return $array;
        }

        foreach (explode('.',$path) as $segment) {
            if (static::exists($array, $segment)) {
                $array = $array[$segment];
            } else {
                return $default;
            }
        }

        return $array;
    }

    /**
     * 设置一个值到指定的位置
     *
     * @param array $array
     * @param string $path
     * @param mixed $value
     * @param bool $push
     * @return array
     */
    public static function set(&$array, $path, $value, $push = false)
    {
        if (!is_string($path) || $path == '') {
            throw new \InvalidArgumentException(
                '[$path] must be a string and not empty '
            );
        }

        $path = explode('.',$path);
        $lastKey = array_pop($path);

        foreach ($path as $key) {
            if (!static::exists($array, $key)) {
                $array[$key] = [];
            }

            // 对象传递过程中默认引用
            if ($array[$key] instanceof ArrayAccess) {
                $array = $array[$key];
            } else {
                $array = &$array[$key];
            }
        }

        if ($push) {
            // 兼容ArrayAccess
            $array[$lastKey][] = $value;
        } else {
            $array[$lastKey] = $value;
        }
    }

    /**
     * 将参数添加到指定路径的末尾
     *
     * @param array $array
     * @param $path
     * @param $value
     */
    public static function push(&$array, $path, $value)
    {
        static::set($array, $path, $value, true);
    }

    /**
     * 检查key是否存在
     *
     * @param array|ArrayAccess $array
     * @param array|mixed $keys
     * @return bool
     */
    public static function has($array, $keys)
    {
        $keys = (array) $keys;

        if (!$array || $keys === []) {
            return false;
        }

        foreach ($keys as $key) {
            $subKeyArray = $array;

            if (static::exists($array, $key)) {
                continue;
            }

            foreach (explode('.', $key) as $segment) {
                if (static::exists($subKeyArray, $segment)) {
                    $subKeyArray = $subKeyArray[$segment];
                } else {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * 删除数组中的一个元素
     *
     * @param $array
     * @param $keys
     */
    public static function forget(&$array, $keys)
    {
        $original = &$array;

        $keys = (array) $keys;

        if (count($keys) === 0) {
            return;
        }

        foreach ($keys as $key) {
            if (static::exists($array, $key)) {
                unset($array[$key]);

                continue;
            }

            $parts = explode('.', $key);

            // 将数组重置为原输入数组
            $array = &$original;

            while (count($parts) > 1) {
                $part = array_shift($parts);

                if (isset($array[$part]) && is_array($array[$part])) {
                    $array = &$array[$part];
                } else {
                    continue 2;
                }
            }

            unset($array[array_shift($parts)]);
        }
    }

    /**
     * 分离key跟value
     *
     * @param array $array
     * @return array
     */
    public static function detach(array $array)
    {
        return [array_keys($array),array_values($array)];
    }

    /**
     * 将数组中的每一个元素通过递归回调一次
     *
     * @param array $array      要被回调的数组
     * @param callable $call    回调函数
     * @param int $depth        递归深度
     * @return array
     */
    public static function recursiveCall(array $array, callable $call, $depth = 512)
    {
        if ($depth-- <= 0) {
            return $array;
        }

        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $array[$key] = static::recursiveCall($value, $call, $depth);
            } else {
                $array[$key] = call_user_func($call,$value);
            }
        }

        return $array;
    }

    /**
     * 从数组中取出指定的值
     *
     * @param array|ArrayAccess $array
     * @param array $keys
     * @return array
     */
    public static function take($array, array $keys)
    {
        $result = [];
        foreach ($keys as $key) {
            if (static::exists($array, $key)) {
                $result[] = $array[$key];
            }
        }

        return $result;
    }

    /**
     * 将回调函数作用到给定数组的指定的单元上
     *
     * @param array $array
     * @param array $elements
     * @param string $func
     * @return array
     */
    public static function handleElement(array $array, array $elements, $func = 'rawurlencode')
    {
        if (!is_callable($func)) {
            throw new InvalidArgumentException("parameter 3 must be a callable");
        }

        foreach ($elements as $key) {
            if (static::exists($array, $key)) {
                $array[$key] = $func($array[$key]);
            }
        }

        return $array;
    }

    // Todo 将后续函数移植到验证类中
    /**
     * 从数组获取指定数据
     *
     * @param $array
     * @param $keys
     * @return array
     */
    public static function getKeywords($array, array $keys)
    {
        if (!static::accessible($array)) {
            throw new RuntimeException("parameter 1 must be array");
        }

        $result = [];
        foreach ($keys as $key) {
            if (!static::exists($array, $key)) {
                // 缺少必要参数
                throw new RuntimeException("\"{$key}\" does not exist");
            }

            $result[$key] = $array[$key];
        }

        return $result;
    }

    /**
     * 检查非法字段
     *
     * @param $array
     * @param array $keys
     * @return array
     */
    public static function checkIllegalKeywords($array,array $keys)
    {
        if (!static::accessible($array)) {
            throw new RuntimeException("parameter 1 must be array");
        }

        foreach ($keys as $key) {
            if (static::exists($array, $key)) {
                // 非法参数
                throw new RuntimeException("\"{$key}\" is an illegal argument");
            }
        }

        return true;
    }
}