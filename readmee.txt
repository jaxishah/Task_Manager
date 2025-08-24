task_manager_test
 
 task_manager 
 create 2 db
 
 
 
 run unit test case
 php yii migrate --migrationPath=@app/migrations --interactive=0 --appconfig=config/test_console.php   - migration
 php vendor\bin\phpunit --testdox
 
 
 php yii migrate
 
 
 https://documenter.getpostman.com/view/1660822/2sB3BLk8W3