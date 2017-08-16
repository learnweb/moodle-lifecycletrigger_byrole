# moodle-cleanupcoursestrigger_byrole
[WIP] This is a trigger-Subplugin for the admin tool [moodle-tool_cleanupcourses](https://github.com/learnweb/moodle-tool_cleanupcourses). 
Course without a reponsible person are marked for the cleanupprocess of the cleanupcourses admin tool.
## Settings
Site administrators choose between all available roles for responsible roles. When multiple roles are selected it 
is sufficient if at least one role is represented in the course. 

Additionally, administrators determine a time period which 
serves as a queue time until a course is marked for the cleanup process.
This functionality assures that courses are not altered when roles are merely changed temporary.

## Proceeding
A trigger plugin always receives one course. To determine whether the course should be deleted the plugin 
checks which roles are present in the course. When at least one responsible role is present the course will not be triggered. 
Courses that have no responsible person are saved in the database with a timestamp. 
When a course has no responsible person and a entry in the table and the timestamp 
is sufficiently old, the course is triggered for the cleanup process of the admin tool.
  
 For detailed information on trigger plugins visit the 
[Wiki](https://github.com/learnweb/moodle-tool_cleanupcourses/wiki) of the cleanupcourses admin tool.