Note: I have used PHP Version 8.1.25

 
1. Project setup instructions (Yii2 install, DB config, migrations)
    Clone the project in the specified folder
        git clone https://github.com/jaxishah/Task_Manager.git
        
    Install Dependencies
        composer install

    Setup Database
        Create two databases : task_manager, task_manager_test
        php yii migrate

    Run local server
        php yii serve --port=8080

    Hit below URL:
        http://localhost:8080/task/

        

2. API Endpoint List:

    Create Task         - http://localhost:8080/api/task - POST
    Get task view       - http://localhost:8080/api/task/2 - GET
    UpdateTask          - http://localhost:8080/api/task/13 - PATCH
    Get all tasks       - http://localhost:8080/api/task - GET
    Delete Task         - http://localhost:8080/api/task/2 - DELETE
    Delete Task Restore - http://localhost:8080/api/task/2/restore - PATCH
    Toogle task status  - http://localhost:8080/api/task/11/toggle-status - PATCH
    Get all tags        - http://localhost:8080/api/tags?search=demo - GET

3. How to run and test the frontend
    Follow step 1 and hit the URL - http://localhost:8080/task/
    Main Url : http://localhost:8080/task/index - Get all tasks
                    - Filter by status & priority
                    - Pagination
                    - Delete task
               http://localhost:8080/task/create - Create Task
               http://localhost:8080/task/view?id=1 - Delete Task
               http://localhost:8080/task/update?id=1 - UpdateTask


4. Any assumptions or known issues
    I have done 
        -> For better view perspective, installed select2 for tag selection in create/update task in frontend
        -> tag should be populated while create/update task for selecion
        -> user can either enter new tag or select existing one
        -> Tag list API also given for listing tag in createForm in frontside
    
    Not Done : 
        -> Title should not have duplicate record and due date must be future date.(this can be done by validation rule-)  

    FOR PHPUnit test cases
		- run following command for migrate in test db
			 php yii migrate --migrationPath=@app/migrations --interactive=0 --appconfig=config/test_console.php   - migration
             php vendor\bin\phpunit --testdox   
            

5. (Optional) Postman collection or curl commands
    https://documenter.getpostman.com/view/1660822/2sB3BLk8W3

    -> list all apis here with examples which includes all valid/invlaid repsonses
