# Welcome to IBM® Spectrum LSF RTM Server !


IBM® Spectrum LSF RTM (RTM) is an operational dashboard for IBM Spectrum LSF environments that provides comprehensive workload monitoring, reporting, and management. It makes cluster administrators more efficient in their day-to-day activities and provides the information and tools that are needed to improve cluster efficiency, enable better user productivity, and contain or reduce costs.

RTM caters to three user groups who are each responsible for monitoring and reporting in the LSF environment. LSF administrators, who are responsible for monitoring and maintaining the LSF clusters and application license servers, are the most common users of RTM.

Unlike other monitoring tools that focus on just one facet of cluster monitoring, RTM provides a complete, integrated monitoring facility that is designed specifically for LSF environments. Multiple clusters can be monitored easily and effectively through a single intuitive interface.

RTM is workload and resource-aware providing full visibility to LSF clusters. It provides comprehensive workload monitoring, reporting, and management tools. Using RTM you can monitor and graph LSF resources (including networks, disks, applications, and others) in a cluster. In graph or report formats, RTM displays resource-related information such as the number of jobs that are submitted, the details of individual jobs (like load average, CPU usage, job owner), or the hosts on which the jobs ran.

FLEXlm(Flexera Software FlexNet) and RLM(Reprise License Manager) are the third-party license manager that are monitored by RTM for license usage report.

# Distributions and Support

- the release/current branch is also available to entitled customers from IBM Passport Advantage
- support is available to entitled customers from IBM for the release branch

# Assumption

IBM® Spectrum LSF RTM Server presents data from database which is loaded by data loaders. It works well with IBM Spectrum LSF RTM Pollers 10.2.x. You could also create your own
data poller by following the provided data schema in rtm.sql.

Cacti is a complete RRDTool-based graphing solution that is developed by The Cacti Group. RTM uses Cacti as a rich graphical user interface framework to provide monitoring, reporting, and alerting functions specifically for the LSF environment. The LSF capabilities are included as a Cacti plug-in so that when used together, RTM can offer LSF-specific monitoring and reporting capabilities. These features are in addition to the standard capabilities that you would normally get from the open source Cacti package.

This documentation assumes that you are familiar with Cacti. For an introduction to Cacti itself, and for information specific to Cacti, refer to the Cacti documentation at cacti.net/documentation.php.

To ensure that all data are collected efficiently from the LSF environment, IBM provides specific data pollers that work with RTM. You are free to use your own data pollers to work with RTM, but IBM does not provide support for those custom data pollers.

# Getting Started

RTM Server should be able to run on any Linux with the following requirements:

- PHP 5.4+

- MySQL/MariaDB 5.5+

- RRDtool 1.3+, 1.5+ recommended

- NET-SNMP 5.5+

- Web Server with PHP support

PHP Must also be compiled as a standalone cgi or cli binary. This is required
for data gathering via cron.

1. Prepare LAMP(Linux, Apache, MySQL/MariaDB, PHP) environment by [Cacti installation guide](https://github.com/Cacti/documentation/blob/develop/README.md#cacti-installation), and [Installing IBM Spectrum LSF RTM
](https://www.ibm.com/docs/en/spectrum-lsf-rtm/10.2.0?topic=migrating-installing-spectrum-lsf-rtm)
2. Update MySQL/MariaDB configuration:
```ini
[mysqld]

# required for multiple language support
character_set_server = utf8mb4
collation_server = utf8mb4_unicode_ci

# Memory tunables
max_heap_table_size = XXX
max_allowed_packet = 16M
tmp_table_size = XXX
join_buffer_size = XXX
sort_buffer_size = XXX

# important for compatibility
sql_mode=ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION

# innodb settings
# You can set .._buffer_pool_size up to 50 - 80 %
# of RAM but beware of setting memory usage too high
innodb_buffer_pool_size = XXX
innodb_buffer_pool_instances = XXX

# Set .._log_file_size to 25 % of buffer pool size
innodb_log_file_size = XXX
innodb_log_buffer_size = XXX

innodb_sort_buffer_size = XXX

innodb_doublewrite = ON
innodb_flush_log_at_trx_commit = 2

# required
innodb_file_per_table = ON
innodb_file_format = Barracuda
innodb_large_prefix = 1

# not all version support
innodb_flush_log_at_timeout = 3

# for SSD's/NVMe
innodb_read_io_threads = 32
innodb_write_io_threads = 16
innodb_io_capacity = 10000
innodb_io_capacity_max = 20000
innodb_flush_method = O_DIRECT
```

* The *required* settings are very important.  Otherwise, you will encounter issues. The settings with XXX, you can configure by your hardware specification.
* Restart mysql/mariadb service after modify configuration.

3. Create mysql database:
```SQL
CREATE DATABASE `cacti`
```
4. Load timezone information into database:
```bash
mysql_tzinfo_to_sql /usr/share/zoneinfo | mysql -u root mysql
```
5. Create and grant database user:
```SQL
CREATE USER 'cacti'@'localhost' IDENTIFIED BY 'admin';
GRANT ALL PRIVILEGES ON cacti.* TO 'cacti'@'localhost'
GRANT SELECT ON mysql.time_zone_name TO 'cacti'@'localhost';
```
6. Import rtm.sql to create RTM database
```bash
mysql -ucacti -padmin cacti < rtm.sql
```

# Documentation

## Official Doc
RTM Documentation is available with the IBM Spectrum LSF RTM releases and also available for viewing on the [Documentation](https://www.ibm.com/docs/en/spectrum-lsf-rtm).

## Contributions

We welcome contributions, feature requests, and suggestions. Here is the link to open an [issue](https://github.com/IBM/ibm-spectrum-lsf-rtm-server/issues) for any problems you encounter. If you want to contribute, please follow the guidelines in [contributors guidelines](https://github.com/IBM/ibm-spectrum-lsf-rtm-server/blob/main/CONTRIBUTING.md).

## License

This distribution is licensed under GNU GENERAL PUBLIC LICENSE v2.0. More details about license can be found in file LICENSE and NOTICE.
