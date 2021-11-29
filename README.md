# DrupalModule_Students
This is a Drupal 9 module, that implements page for managing students inrolled in a course.
The module adds students_students and students_groups gatabases.
The 'students_students' database contains information about students, such as name, last name, email address etc.
The 'students_groups' database contains information about groups (currently - color, used to mark corresponding group in students' list, later also assigned to droup assistant's ID, cabinet, etc.)

The module introduces 2 blocks: BirthdayBlock and StudentListFunctionalityBlock.
BirthdayBlock shows students who have theit birthdays this month, allowing us to send them a congratulation email.
StudentListtFunctionalityBlocks contains instruments for managing table (currently - colors, used for different groups, later there could be filter/sort variants)

Students module allows viewing students by accessing '/web/students/list' page. By clocking on student's ID we can view information about that student.
