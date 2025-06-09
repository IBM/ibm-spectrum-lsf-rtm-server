# lsfenh

## Summary

This plugin does purely data collection and analytics for RTM.  It does this
for each polling cycle and stores the data into tables for use in either
Cacti Graphs, or other Analytic platforms.  It stores data in a number of tables.

## Installation

Simply Install and Enable the Plugin as you would normally do.  Track the data
collection times to ensure that your RTM system can handle the additional load.

## Usage

Either Create Graphs using standard Graph Templates or export the data to external
sources so that they can be consumed by them.

## Tables Created

* grid_sla_stats                   - Classic RTM Statistics for SLA's
* grid_sla_memory_buckets          - Information about memory distribution by bucket size in an SLA
* grid_sla_memory_buckets_finished - Historical Daily Information about memory distribution by bucket size in an SLA
* grid_djob_hstats                 - Data about Recently Done Jobs for Hosts
* grid_guarantees                  - A table that includes information about Guarantee Pools
* grid_pool_memory                 - Information about Memory Status for Guarantee Pools
* grid_pool_host_lending           - Information About lending activities in your Guarantee Pools
* grid_reason_classes              - A table that allows you to map Pending Reasons to Classes
* grid_hostgroup_definitions       - The actual host group definition information when running bmgroup
* grid_jobs_summary                - Brief information about active PEND, RUN, SUSP jobs
* grid_job_groups                  - Classic RTM Statistics for Job Groups
* grid_jobs_planned                - Active Jobs that were dispatched based upon a Plan
* grid_clusters_maxcpus_bymodel    - The maximum CPU's by Host Model in Clusters
* grid_jobs_reasons                - Raw Pending Reason Summary information from the Cluster
* grid_jobs_reason_details         - Processed Pending Reason Summary information at various levels
* grid_jobs_reason_summary         - Summary Pending Job Information Translated for Readability
* grid_resource_descriptions       - Actual Descriptions for LSF Shared Resources
* grid_hosts_perf_stats            - Host Utilization information important to LSF Administrators
* grid_queues_perf_stats           - Queue Level Utilization information important to LSF Administrators
* grid_hostgroups_perf_stats       - Host Group Level Utilization information important to LSF Administrators

## How Data is Populated

The Data Collector is launched once per polling interval for the LSF Cluster.  In the case of a 1 Minute 
Polling frequency the binary 'poller_analytics.php' is launched to collect and aggregate all data
A launcher is launched for every host called 'poller_lsf.php' which collects jobs and pending
reason data and stores in the tables above.

The poller_buckets.php will scan all your historical jobs records and populate the 
grid_sla_memory_buckets_finished table.  This binary can be run by hand once a day, or week depending on
how often you use the data.  However, it is not populated automatically.

## Authors

Larry Adams, who worked at Platform Computing and IBM from 2006 through 2021 developed this plugin
working with customers around the world.  Though the plugins where this data existing varies, this 
collection of data collectors is an attempt to provide the data without the rest of the plugins
overhead and bugs.

Cacti has always been Open Source and GPL, and I hope it remains that way for years to come.

-----------------------------------------------
Copyright (c) 2004-2025 - The Cacti Group, Inc.
Copyright (c) 2006-2012 - Platform Computing, Inc.
Copyright (c) 2012-2025 - IBM Corporation, Inc.

