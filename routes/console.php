<?php

use Illuminate\Support\Facades\Schedule;

// รัน command ปิดประมูลทุก ๆ 1 นาที
Schedule::command('auctions:close-expired')->everyMinute();

// แจ้งเตือนใกล้หมดเวลาประมูล (1 ชม. + 15 นาที) + แจ้ง watchers เมื่อเปิดประมูล
Schedule::command('auctions:notify-ending-soon')->everyMinute()->withoutOverlapping();