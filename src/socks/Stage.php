<?php
namespace Ant\Network\Socks;


class Stage
{
    // 初始化阶段
    const INIT = 0;
    // 认证阶段
    const AUTH = 1;
    // 运行阶段
    const RUNNING = 2;

    public static function isStage($stage)
    {
        return is_int($stage) && $stage > 0 && $stage < 2;
    }
}