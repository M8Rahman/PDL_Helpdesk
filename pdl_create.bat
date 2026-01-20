@echo off
echo Creating PDL Helpdesk structure...

REM Create directories
mkdir "D:\Xampp\htdocs\PDL_Helpdesk\config"
mkdir "D:\Xampp\htdocs\PDL_Helpdesk\auth"
mkdir "D:\Xampp\htdocs\PDL_Helpdesk\admin"
mkdir "D:\Xampp\htdocs\PDL_Helpdesk\user"
mkdir "D:\Xampp\htdocs\PDL_Helpdesk\support"
mkdir "D:\Xampp\htdocs\PDL_Helpdesk\actions"
mkdir "D:\Xampp\htdocs\PDL_Helpdesk\assets"

REM Create files
cd /d "D:\Xampp\htdocs\PDL_Helpdesk"

type nul > config\db.php
type nul > auth\login.php
type nul > auth\logout.php
type nul > admin\dashboard.php
type nul > admin\users.php
type nul > admin\add_user.php
type nul > user\dashboard.php
type nul > support\dashboard.php
type nul > actions\create_ticket.php
type nul > actions\reply_ticket.php
type nul > actions\solve_ticket.php
type nul > assets\style.css
type nul > index.php

echo Structure created successfully!
pause