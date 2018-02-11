#####How to run
1. Create project in the Google Developers Console: follow list items a-d from the section "Step 1: Turn on the Google Sheets API"
[in this instruction](https://developers.google.com/sheets/api/quickstart/php#step_1_turn_on_the_api_name).
1. [Follow instructions in section "Create authorization credentials"](https://developers.google.com/api-client-library/php/auth/web-app#creatingcred). 
create credentials, download `client_secret.json` and put
`client_secret.json` into this project root.
1. Give web-server (e.g. Apache) writing rights on `/credentials/` and `/logs/` directories inside project.


Free limits of **Google Sheets API**
Action | Limit
--- | ---
**Read** requests per 100 seconds | 500
**Read** requests per 100 seconds per user | 100
**Read** requests per day | Unlimited
**Write** requests per 100 seconds | 500
**Write** requests per 100 seconds per user | 100
**Write** requests per day | Unlimited
