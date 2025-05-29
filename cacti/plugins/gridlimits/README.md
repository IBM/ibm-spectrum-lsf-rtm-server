# gridlimits

The gridlimits plugin allows viewing of LSF limits usage for a variety
of LSF limits cases.  It allows the users of the gridlimits plugin
to view limits by user, group, queue, etc. over upto a two week period.

It uses FusionCharts that are included in the RTM product to render 
visually pleasant charts which makes them more easily presentable.

## Core Features

* View Limits Use for all LSF Cluster

* View Graphs of Limits Usage for Upto Two Weeks

## Limitations

* Does not support Global Limits at this time.

* Limits by Application Profile, though collected, are not displayed.

## Installation

To install the gridlimits plugin, follow the steps below:

1. Goto the RTM console and install and enable the plguin.  This
   step will create the base tables required by the data collector.

2. Copy the binary file in the bin plguin directory to the various 
   RTM data collector directories.  The default location for the RTM 
   data collectors is /opt/IBM/rtm/lsf*/bin.

3. Users with 'General LSF Data' permission will automatically be
   able to view the Limits data under the Cluster top tab under
   Reports > Limits page.

4. Review the Cacti log for errors.  If you find errors report them
   to IBM or submit a pull request to make updates.

## Possible Bugs and Feature Enhancements

Bug and feature enhancements for the gridlimits plugin are handled in GitHub
or through your contract with IBM. If you find a bug, reach out to IBM and
open a ticket or alternatively, open a pull request on GitHub.

## Authors

The gridlimits plugin was initially developed by Sean McCombe several years
ago.  It has been enhanced recently by Larry Adams (aka TheWitness).

