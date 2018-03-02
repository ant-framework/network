<?php
namespace Test;

use Ant\Support\Arr;
use Ant\Support\Collection;

class ArrTest extends \PHPUnit_Framework_TestCase
{
    /**
     * 测试 accessible 方法
     */
    public function testAccessible()
    {
        $this->assertTrue(Arr::accessible(['foo' => 'bar']));
        $this->assertFalse(Arr::accessible('foobar'));

        $collection = new Collection();
        $this->assertInstanceOf(\ArrayAccess::class, $collection);
        $this->assertTrue(Arr::accessible($collection));
        $this->assertFalse(Arr::accessible(new \stdClass()));
    }

    /**
     * 测试 collapse 方法
     */
    public function testCollapse()
    {
        $array = [
            'test',
            ['foo','bar'],
            new Collection(['a','b','c'])
        ];

        $this->assertEquals(['foo','bar','a','b','c'], Arr::collapse($array));
    }

    /**
     * 测试 flatten 方法
     */
    public function testFlatten()
    {
        $collection = new Collection(['a','b','c']);
        $array = [
            'test',
            ['foo','bar',$collection],
        ];
        $this->assertEquals(['test','foo','bar','a','b','c'], Arr::flatten($array));
        $this->assertEquals(['test','foo','bar',$collection], Arr::flatten($array,1));


        $complexArray = ['a','b',['c','d',['e','f',['g','h',['i','j',['k','l']]]]]];
        $this->assertEquals(['a','b','c','d','e','f','g','h','i','j','k','l'], Arr::flatten($complexArray));
    }

    /**
     * 测试 removeEmpty 方法
     */
    public function testRemovalEmpty()
    {
        $array = [
            'a' =>  ' ',
            'b' =>  '',
            'c' =>  null,
            'd' =>  false
        ];

        Arr::removalEmpty($array);
        $this->assertEquals(['a' =>  ' ','d' => false], $array);
    }

    /**
     * 测试 exists 方法
     */
    public function testExists()
    {
        $array = [
            'foo'   =>  'bar',
            'test'  =>  null,
        ];

        $this->assertTrue(Arr::exists($array, 'foo'));
        $this->assertTrue(Arr::exists($array, 'test'));
        $this->assertFalse(Arr::exists($array, 'bar'));

        $arrayAccessObj = new Collection($array);
        $this->assertTrue(Arr::exists($arrayAccessObj, 'foo'));
        $this->assertTrue(Arr::exists($arrayAccessObj, 'test'));
        $this->assertFalse(Arr::exists($arrayAccessObj, 'bar'));
    }

    /**
     * 测试 get 方法
     */
    public function testGet()
    {
        $array = ['a' => ['b' => ['c' => ['d' => 'foobar']]]];

        $this->assertEquals($array, Arr::get($array, null));
        $this->assertEquals(null, Arr::get($array, 'a.b.c.e'));
        $this->assertEquals('test', Arr::get($array, 'a.b.c.e', 'test'));
        $this->assertEquals('foobar', Arr::get($array,'a.b.c.d'));
    }

    /**
     * 测试 set 方法
     */
    public function testSet()
    {
        $array = [];
        Arr::set($array, 'a.b.c.d', 'foobar');
        $this->assertEquals(['a' => ['b' => ['c' => ['d' => 'foobar']]]], $array);

        // 夹杂ArrayAccess的使用
        Arr::set($array, 'a', new Collection(['b' => ['c' => ['d' => 'foobar']]]));
        $this->assertEquals('foobar', Arr::get($array,'a.b.c.d'));
    }

    /**
     * 测试 push 方法
     */
    public function testPush()
    {
        $array = [];
        Arr::push($array, 'a.b.c.d', 'foobar');
        $this->assertEquals(['a' => ['b' => ['c' => ['d' => ['foobar']]]]], $array);
    }

    /**
     * 测试 has 方法
     */
    public function testHas()
    {
        $array = ['a' => ['b' => ['c' => false]]];

        $this->assertTrue(Arr::has($array, 'a.b.c'));
        $this->assertFalse(Arr::has($array, 'a.b.d'));
    }

    /**
     * 测试 forget 方法
     */
    public function testForget()
    {
        $array = ['a' => ['b' => ['c' => false , 'd' => '123']]];

        Arr::forget($array, ['a.b.c','a.b.d']);
        $this->assertEquals(['a' => ['b' => []]],$array);
    }

    /**
     * 测试 detach 方法
     */
    public function testDetach()
    {
        $array = [
            'foo'   =>  'bar',
            'test'  =>  ['a','b','c']
        ];

        list($keys, $values) = Arr::detach($array);

        $this->assertEquals(['foo','test'], $keys);
        $this->assertEquals(['bar',['a','b','c']], $values);
    }
}