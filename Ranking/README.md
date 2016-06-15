Moodle Ranking block repository
===============================

>Edit: Kiya Govek changed Ranking points block to Badge leaderboard block
>Credit: Willian Mano for original Ranking block

>This block improves gamification of a Moodle course by creating a leaderboard for badges.


Update Notes
------------
> Changed SQL to assign points based on number of badges earned
> Changed interface to accomodate groups rather than weekly/monthly
> TODO: Change to show students/groups with 0 points

TODO
------------
> Change language files to mimic point to badge conversion
> Change block name to mimic point to badge conversion
> Complete SQL conversion to badges
> Edit visuals as needed


Installation
------------

**First way**

- Clone this repository into the folder blocks.
- Access the notification area in moodle and install

**Second way**

- Download this repository
- Extract the content
- Put the folder into the folder blocks of your moodle
- Access the notification area in moodle and install

Post instalation
----------------
After you have installed the block you just add it into the moodle course.

> The ranking block works together with badges, so you need to enable that and configure badges to be awarded.


**OBS:** The ranking block needs the moodle cron configured and working fine. Read the moodle documentation about the cron file (for more information..)