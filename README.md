# govcms_reminders

Some time was available so a generic govcms_reminder module was mapped out on a whiteboard and our approach coded, this is a generic module for govcms reminders. 

First some definitions;

* A reminder

A reminder is the email sent based on the reminder type to the specificed user for the mapped entity type.

* A reminder type

A reminder type is the configuration of the reminder template mapped to the entity type. For a reminder to be sent out there first needs to be a configured reminder type, then the actual entity itself needs to be set to recieve the reminder based on a single or recurring request.

# Overview

This module creates reminders for mapped entity types using templates which can include tokens. The templates are bound to a entity type. Multiple reminder type configurations can be bound to a particular entity type. Once the configuration is created each entity type will have options in the appropraite editing UI to set the reminders. The editing UI will include options to set a single or recurring reminder - this is based on the Path module and the approach contained within that module.

# Install

Install the module in the usual way - it requires no dependencies at all.

# Usage

Once installed the first step it to set a reminder type configuration. Once set reminders can be created for the entity type configured.

Freely provided to the govCMS community - all testing, feedback and critique to rod.higgins@senuamedia.com. Thanks.