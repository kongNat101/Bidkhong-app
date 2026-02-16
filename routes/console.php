<?php

use Illuminate\Support\Facades\Schedule;

// รัน command ปิดประมูลทุก ๆ 1 นาที
Schedule::command('auctions:close-expired')->everyMinute();