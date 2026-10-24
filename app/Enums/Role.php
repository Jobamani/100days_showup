<?php

namespace App\Enums;

enum Role: string
{
    case User = 'user';
    case Editor = 'editor';
    case Admin = 'admin';   
}