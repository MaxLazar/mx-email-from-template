#MX Email From Template for ExpressionEngine 5
MX Email From Template is add-on which helps you to send emails right from EE template.

Initially, I did it from one of my clients who has needed to send a report by email with data from channel entries inside the CSV file. When EE5  issued, I decided to add some extra settings and release it for the community.


##Installation

* Place the **mx_email_from_template** folder inside your **user/addons** folder
* Go to **cp/addons** and install *MX Email From Template*.

##Parameters settings
**to** - destination email address. Accepts multiple email addresses, comma-separated. (default: site settings, "address")

**cc** - [optional] email address to carbon-copy

**bcc** - [optional] email address to blind-carbon-copy

**from** - sender email address (default: site settings, "address")

**subject** - email subject line (default: URI)

**decode_subject_entities** - Set to "no" if you don't want to parse the HTML entities in the subject line.

**decode_message_entities** - Set to "no" if you don't want to parse the HTML entities in the message text.

**mailtype** - "text" or "html"

**alt_message** - [optional] a text-only message (for email clients that don't support HTML email)

**redirect** [optional] redirect url

**echo** - Set to "on" if you want to display the tag contents in the template.

##Config.php

##Tags

{to}

{cc}

{bcc}

{from}

{subject}

{ip}

{httpagent}

{uri_string}

##Tags pairs

**{files} {/files}** list of files for attachment (server paths)

**{csv:file} {/csv:file}** data for csv file

##Examples

### send email

	{exp:mx_email_from_template to="admin@example.com" from="admin@example.com" subject="What would Han Solo do?"}
	   This tag content is being viewed at {uri_string} by {httpagent}. Sending notification to {to}.
	{/exp:mx_email_from_template}

### send email with attachment

	{exp:mx_email_from_template to="admin@example.com" from="admin@example.com" subject="You're my only hope"}
	   You must see this droid safely delivered to him on Alderaan. This is our most desperate hour. Help me, Obi-Wan Kenobi. You're my only hope.

	    {attachment}
        [/files/Death_Star_Technical_Manual_blueprints.jpg]
        [/files/Death_Star_Technical_Manual_blueprints_planet.jpg]
       {/attachment}
	{/exp:mx_email_from_template}

### send email with generated file
	{exp:mx_email_from_template to="admin@example.com" from="admin@example.com" subject="information about your account" parser="inward"}
	You can find data in attachment.
	Thanks for you help!

	{csv:file}
	id,chain,dept,category,company,brand,date,productsize,productmeasure,purchasequantity,purchaseamount
	86246,205,7,707,1078778070,12564,2012-03-02,12,OZ,1,7.59 86246,205,63,6319,107654575,17876,2012-03-02,64,OZ,1,1.59 86246,205,97,9753,1022027929,0,2012-03-02,1,CT,1,5.99 86246,205,25,2509,107996777,31373,2012-03-02,16,OZ,1,1.99 86246,205,55,5555,107684070,32094,2012-03-02,16,OZ,2,10.38
	{/csv:file}
	{/exp:mx_email_from_template}

##Support Policy

This is Communite Edition (CE) add-on.

##Contributing To MX Email From Template

Your participation to MX Email From Template development is very welcome!

You may participate in the following ways:

* [Report issues](https://github.com/MaxLazar/mx-email-from-template/issues)


##License

The MX Email From Template for ExpressionEngine 3 is open-sourced software licensed under the [MIT license](http://opensource.org/licenses/MIT)

##Thanks To
[Michael Rog](https://rog.ee/email_from_template/)
