<?php
namespace Test;

use Ant\Support\Str;

class StrTest extends \PHPUnit_Framework_TestCase
{
    /**
     * ²âÊÔ contains º¯Êý
     */
    public function testContains()
    {
        $this->assertTrue(Str::contains('foobar','foo'));
        $this->assertTrue(Str::contains('foobar','bar'));
        $this->assertTrue(Str::contains('foobar',['zzz','far','bar']));
        $this->assertFalse(Str::contains('foobar','zzz'));
    }

    /**
     * ²âÊÔ startsWith º¯Êý
     */
    public function testStartsWith()
    {
        $this->assertTrue(Str::startsWith('foobar','foo'));
        $this->assertFalse(Str::startsWith('foobar','bar'));
    }

    public function testEndsWith()
    {
        $this->assertTrue(Str::endsWith('foobar','bar'));
        $this->assertFalse(Str::endsWith('foobar','foo'));
    }

    public function testReplaceFirst()
    {
        $this->assertEquals('foobar' ,Str::replaceFirst('fii','foo','fiibar'));
        $this->assertEquals('foobar' ,Str::replaceFirst('iib','oob','fiibar'));
        $this->assertEquals('abcdef' ,Str::replaceFirst('def','abc','defdef'));
    }

    public function testReplaceLast()
    {
        $this->assertEquals('foobar' ,Str::replaceLast('fii','foo','fiibar'));
        $this->assertEquals('foobar' ,Str::replaceLast('iib','oob','fiibar'));
        $this->assertEquals('defabc' ,Str::replaceLast('def','abc','defdef'));
    }

    public function testReplaceArray()
    {
        $this->assertEquals('abcdef' ,Str::replaceArray('f',['a','b','c','d','e'],'ffffff'));
    }
}