bin/cake acl_extras aco_sync
bin/cake acl grant Groups.1 controllers
bin/cake acl grant Groups.5 controllers  #Remove global assignment for finance
#bin/cake acl grant Groups.4 controllers #TODO: Remove this global assignment
bin/cake acl grant Groups.2 controllers/Users/home
bin/cake acl grant Groups.3 controllers/Users/home
bin/cake acl grant Groups.4 controllers/Users/home
bin/cake acl grant Groups.2 controllers/Users/profile
bin/cake acl grant Groups.3 controllers/Users/profile
bin/cake acl grant Groups.4 controllers/Users/profile
bin/cake acl grant Groups.4 controllers/Applicant/Users
bin/cake acl grant Groups.4 controllers/Applicant/Applications
bin/cake acl grant Groups.4 controllers/Applications/add
bin/cake acl grant Groups.4 controllers/Applications/edit
bin/cake acl grant Groups.4 controllers/Applications/view
bin/cake acl grant Groups.4 controllers/Applications/delete
bin/cake acl grant Groups.4 controllers/Attachments/download
