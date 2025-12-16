<?php
// $Id$
/*
 +-------------------------------------------------------------------------+
 | Copyright IBM Corp. 2006, 2022                                          |
 |                                                                         |
 | Licensed under the Apache License, Version 2.0 (the "License");         |
 | you may not use this file except in compliance with the License.        |
 | You may obtain a copy of the License at                                 |
 |                                                                         |
 | http://www.apache.org/licenses/LICENSE-2.0                              |
 |                                                                         |
 | Unless required by applicable law or agreed to in writing, software     |
 | distributed under the License is distributed on an "AS IS" BASIS,       |
 | WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.|
 | See the License for the specific language governing permissions and     |
 | limitations under the License.                                          |
 +-------------------------------------------------------------------------+
*/

include_once($config['base_path'] . '/plugins/grid/lib/grid_functions.php');

//RTM
global $rtm, $rtmvermap, $signal, $lsf_versions, $oob_lsf_versions;
global $config;

$path_rtm_top=grid_get_path_rtm_top();
if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN'){
	// if (getenv('PROCESSOR_ARCHITECTURE') == 'x86') {
	if (file_exists('C:/Program Files (x86)/Platform Computing/RTM')) {
		define('RTM_ROOT', 'C:/Progra~2/Platfo~1/RTM');
	}else{
		define('RTM_ROOT', 'C:/Progra~1/Platfo~1/RTM');
	}
	define('DELIM', '/');
	define('EXT', '.exe');
}else{
	define('RTM_ROOT', $path_rtm_top. '/rtm');
	define('DELIM', '/');
	define('EXT', '');
}

$lsf_versions = array(
/*	'61'     => __('LSF 6.1', 'grid'),
	'62'     => __('LSF 6.2', 'grid'),
	'701'    => __('LSF 7.0 Update 1', 'grid'),
	'702'    => __('LSF 7.0 Update 2', 'grid'),
	'703'    => __('LSF 7.0 Update 3', 'grid'),
	'704'    => __('LSF 7.0 Update 4', 'grid'),
	'705'    => __('LSF 7.0 Update 5', 'grid'),
	'706'    => __('LSF 7.0 Update 6', 'grid'),
	'8'      => __('LSF 8', 'grid'),*/
	'91'     => __('LSF 9.1', 'grid'),
	'1010'   => __('LSF 10.1', 'grid'),
	'1017'   => __('LSF 10.1.0.7', 'grid'),
	'10010013' => __('LSF 10.1.0.13', 'grid')
);

$oob_lsf_versions = array('91', '1010', '1017', '10010013');

//Rename original LSF_SERVERDIR to RTM_POLLERBINDIR, and add new RTM_POLLERBINDIR as acutal eauth location
$rtm = array(
	'lsf91' => array(
		'PATH' => RTM_ROOT . DELIM . 'lsf91' . DELIM,
		'VERSION' => '9.1',
		'LSF_ENVDIR' => RTM_ROOT . DELIM . 'etc' . DELIM,
		'RTM_POLLERBINDIR' => RTM_ROOT . DELIM . 'lsf101' . DELIM . 'bin' . DELIM,
		'LSF_SERVERDIR' => RTM_ROOT . DELIM . 'lsf91' . DELIM . 'etc' . DELIM,
		'DESC' => 'Poller for LSF 9.1'
	),
	'lsf1010' => array(
		'PATH' => RTM_ROOT . DELIM . 'lsf101' . DELIM,
		'VERSION' => '10.1',
		'LSF_ENVDIR' => RTM_ROOT . DELIM . 'etc' . DELIM,
		'RTM_POLLERBINDIR' => RTM_ROOT . DELIM . 'lsf101' . DELIM . 'bin' . DELIM,
		'LSF_SERVERDIR' => RTM_ROOT . DELIM . 'lsf101' . DELIM . 'etc' . DELIM,
		'DESC' => 'Poller for LSF 10.1'
	),
	'lsf1017' => array(
		'PATH' => RTM_ROOT . DELIM . 'lsf1017' . DELIM,
		'VERSION' => '10.1',
		'LSF_ENVDIR' => RTM_ROOT . DELIM . 'etc' . DELIM,
		'RTM_POLLERBINDIR' => RTM_ROOT . DELIM . 'lsf1017' . DELIM . 'bin' . DELIM,
		'LSF_SERVERDIR' => RTM_ROOT . DELIM . 'lsf1017' . DELIM . 'etc' . DELIM,
		'DESC' => 'Poller for LSF 10.1.0.7'
	),
	'lsf10010013' => array(
		'PATH' => RTM_ROOT . DELIM . 'lsf10.1.0.13' . DELIM,
		'VERSION' => '10.1',
		'LSF_ENVDIR' => RTM_ROOT . DELIM . 'etc' . DELIM,
		'RTM_POLLERBINDIR' => RTM_ROOT . DELIM . 'lsf10.1.0.13' . DELIM . 'bin' . DELIM,
		'LSF_SERVERDIR' => RTM_ROOT . DELIM . 'lsf10.1.0.13' . DELIM . 'etc' . DELIM,
		'DESC' => 'Poller for LSF 10.1.0.13'
	)
); // end $rtm

$rtmvermap = array(
    '9.1' => '91',
    '10.1' => '1010',
    '10.1.0.7' => '1017',
    '10.1.0.13' => '10010013'
); // map of version to rtm name

if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN'){
	$signal = array(
		'0' => '',
		'7' => 'SIGKILL',
		'15' => 'SIGCONT',
		'16' => 'SIGSTOP'
	);
} else {
	$signal = array(
		'0' => '',
		'1' => 'SIGHUP',
		'2' => 'SIGINT',
		'3' => 'SIGQUIT',
		'4' => 'SIGILL',
		'5' => 'SIGTRAP',
		'6' => 'SIGABRT',
		'7' => 'SIGBUS',
		'8' => 'SIGFPE',
		'9' => 'SIGKILL',
		'10' => 'SIGUSR1',
		'11' => 'SIGSEGV',
		'12' => 'SIGUSR2',
		'13' => 'SIGPIPE',
		'14' => 'SIGALRM',
		'15' => 'SIGTERM',
		'16' => 'SIGSTKFLT',
		'17' => 'SIGCHLD',
		'18' => 'SIGCONT',
		'19' => 'SIGSTOP',
		'20' => 'SIGTSTP'
	);
}

if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN'){
	define('CREDKEY', "'credkey'");
}

// lsfpollerd
if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN'){
	define('LSFPOLLERD_STOP', 'NET STOP lsfpoller');
	define('LSFPOLLERD_START', 'NET START lsfpoller');
	define('LSFPOLLERD_RESTART', 'NET STOP lsfpoller && NET START lsfpoller');
}else{
	define('LSFPOLLERD_STOP', '/sbin/service lsfpollerd stop');
	define('LSFPOLLERD_START', '/sbin/service lsfpollerd start');
	define('LSFPOLLERD_RESTART', '/sbin/service lsfpollerd restart');
}

/* define some constants */
define('GRID_UNAVAIL',  1);
define('GRID_BUSYCLOSE',2);
define('GRID_IDLECLOSE',3);
define('GRID_LOWRES',   4);
define('GRID_BUSY',     5);
define('GRID_IDLEWJOBS',6);
define('GRID_IDLE',     7);
define('GRID_STARVED',  8);
define('GRID_ADMINDOWN',9);
define('GRID_BLACKHOLE',10);

define('CLUSTER_OK',         1);
define('CLUSTER_RECOVERING', 2);
define('CLUSTER_WARNING',    3);
define('CLUSTER_ALARM',      4);

/* LSF Submit Options */
define('SUB_JOB_NAME',             0x01);       /* job name specified */
define('SUB_QUEUE',                0x02);       /* queue specified */
define('SUB_HOST',                 0x04);       /* execution host(s) specified */
define('SUB_IN_FILE',              0x08);       /* input file specified */
define('SUB_OUT_FILE',             0x10);       /* output file specified */
define('SUB_ERR_FILE',             0x20);       /* error file specified */
define('SUB_EXCLUSIVE',            0x40);       /* exclusive execution specified */
define('SUB_NOTIFY_END',           0x80);       /* send a mail to user when job ends */
define('SUB_NOTIFY_BEGIN',         0x100);      /* send mail to user when job begins */
define('SUB_USER_GROUP',	       0x200);      /* usergroup name */
define('SUB_CHKPNT_PERIOD',        0x400);      /* chkpnt period specified */
define('SUB_CHKPNT_DIR',           0x800);      /* checkpoint directory specified */
define('SUB_CHKPNTABLE',           SUB_CHKPNT_DIR); /* having chkpntdir implies chkpntable */
define('SUB_RESTART_FORCE',        0x1000);     /* restart force specified */
define('SUB_RESTART',              0x2000);     /* it is a restart job */
define('SUB_RERUNNABLE',           0x4000);     /* re-run the job if host down */
define('SUB_WINDOW_SIG',           0x8000);     /* send signal as queue window close */
define('SUB_HOST_SPEC',            0x10000);    /* host/model specified for CPU limit */
define('SUB_DEPEND_COND',          0x20000);    /* depend_cond specified */
define('SUB_RES_REQ',              0x40000);    /* resource request specified */
define('SUB_OTHER_FILES',          0x80000);    /* transfer files specified */
define('SUB_PRE_EXEC',	           0x100000);   /* pre_execute file specified */
define('SUB_LOGIN_SHELL',          0x200000);   /* login shell specified */
define('SUB_MAIL_USER', 	       0x400000);   /* mail results to specified user */
define('SUB_MODIFY',               0x800000);   /* modify job parameters by bmodify */
define('SUB_MODIFY_ONCE',          0x1000000);  /* use modified parameters once */
define('SUB_PROJECT_NAME',         0x2000000);  /* project name for accounting purposes */
define('SUB_INTERACTIVE',          0x4000000);  /* interactive job */
define('SUB_PTY',                  0x8000000);  /* pty mode for interactive job */
define('SUB_PTY_SHELL',            0x10000000); /* pty shell mode for interactive job */
define('SUB_EXCEPT',               0x20000000); /* exception handler for job */
define('SUB_TIME_EVENT',           0x40000000); /* time_event specified */
define('SUB2_HOLD',                0x01);       /* hold option */
define('SUB2_MODIFY_CMD',          0x02);       /* new cmd for bmod */
define('SUB2_BSUB_BLOCK',          0x04);       /* new cmd for bsub block mode */
define('SUB2_HOST_NT',             0x08);       /* submit from NT */
define('SUB2_HOST_UX',             0x10);       /* submit from UNIX */
define('SUB2_QUEUE_CHKPNT',        0x20);       /* submit to a chkpntable queue       */
define('SUB2_QUEUE_RERUNNABLE',    0x40);       /* submit to a rerunnable queue       */
define('SUB2_IN_FILE_SPOOL',       0x80);       /* input file specified with spooling */
define('SUB2_JOB_CMD_SPOOL',       0x100);      /* spool job command                  */
define('SUB2_JOB_PRIORITY',        0x200);      /* job submitted with priority */
define('SUB2_USE_DEF_PROCLIMIT',   0x400);      /* job submitted without -n, use queue's default proclimit */
define('SUB2_MODIFY_RUN_JOB',      0x800);      /* bmod -c/-M/-W/-o/-e */
define('SUB2_MODIFY_PEND_JOB',     0x1000);     /* bmod options only to pending jobs */
define('SUB2_WARNING_TIME_PERIOD', 0x2000);     /* warning time period specified, bsub/bmod -wt */
define('SUB2_WARNING_ACTION',      0x4000);     /* warning action specified, bsub/bmod -wa */
define('SUB2_USE_RSV',             0x8000);     /* bsub -U */
define('SUB2_TSJOB',               0x10000);    /* Terminal service job */
define('SUB2_LSF2TP',              0x20000);    /* special topology for jobs in LSF2 */
define('SUB2_JOB_GROUP',           0x40000);    /* Submit into a job group */
define('SUB2_SLA',                 0x80000);    /* Submit into a service class */
define('SUB2_EXTSCHED',            0x100000);   /* for -extsched */
define('SUB2_LICENSE_PROJECT',     0x200000);   /* License Scheduler project */
define('SUB2_OVERWRITE_OUT_FILE',  0x400000);   /* output overwrite bsub -oo */
define('SUB2_OVERWRITE_ERR_FILE',  0x800000);   /* error overwrite bsub -eo  */
define('SUB2_SSM_JOB',             0x1000000);  /* (symphony) session job */
define('SUB2_SYM_JOB',             0x2000000);  /* (symphony) symphony job */
define('SUB2_SRV_JOB',             0x4000000);  /* (symphony) service(LSF) job */
define('SUB2_SYM_GRP',             0x8000000);  /* (symphony) 'group' job */
define('SUB2_SYM_JOB_PARENT',      0x10000000); /* (symphony) symphony job has child symphony job */
define('SUB2_SYM_JOB_REALTIME',    0x20000000); /* (symphony) symphony job has real time feature */
define('SUB2_SYM_JOB_PERSIST_SRV', 0x40000000); /* (symphony) symphony job has dummy feature to hold all persistent service jobs. */
define('SUB2_SSM_JOB_PERSIST',     0x80000000); /* persistent session job */
define('SUB3_APP',                 0x01);       /* application profile option */
define('SUB3_APP_RERUNNABLE',      0x02);       /* job rerunable because of application */
define('SUB3_ABSOLUTE_PRIORITY',   0x04);       /* job modified with absolute priority */
define('SUB3_DEFAULT_JOBGROUP',    0x08);       /* Submit into a default job group */
define('SUB3_POST_EXEC',           0x10);       /* post_execute file specified*/
define('SUB3_USER_SHELL_LIMITS',   0x20);       /* pass shell limit to execution host*/
define('SUB3_CWD',                 0x40);       /* CWD specified on cmd line*/
define('SUB3_RUNTIME_ESTIMATION',  0x80);       /* runtime estimation */
define('SUB3_NOT_RERUNNABLE',      0x100);      /* not rerunable*/
define('SUB3_JOB_REQUEUE',         0x200);      /* job level requeue exit values */
define('SUB3_INIT_CHKPNT_PERIOD',  0x400);      /* initial checkpoint period */
define('SUB3_MIG_THRESHOLD',       0x800);      /* migration threshold */
define('SUB3_APP_CHKPNT_DIR',      0x1000);     /* checkpoint dir was set by application profile */
define('SUB3_BSUB_CHK_RESREQ',     0x2000);     /* bsub only checks the resreq syntax*/

/* the bit set for jobInfoEnt->exceptMask */
define('J_EXCEPT_MISSCHED',              0x01);
define('J_EXCEPT_OVERRUN',               0x02);
define('J_EXCEPT_UNDERUN',               0x04);
define('J_EXCEPT_JOBEXIT',               0x08);
define('J_EXCEPT_CANTRUN',               0x10);
define('J_EXCEPT_HOSTFAIL',              0x20);
define('J_EXCEPT_STARTFAIL',             0x40);
define('J_EXCEPT_IDLE',                  0x80);
define('J_EXCEPT_RUNTIME_EST_EXCEEDED', 0x100);
define('SPACE',                           '  ');

/* exitInfo bjobs -l*/
define('TERM_UNKNOWN',            0);
define('TERM_PREEMPT',            1);
define('TERM_WINDOW',             2);
define('TERM_LOAD',               3);
define('TERM_OTHER',              4);
define('TERM_RUNLIMIT',           5);
define('TERM_DEADLINE',           6);
define('TERM_PROCESSLIMIT',       7);
define('TERM_FORCE_OWNER',        8);
define('TERM_FORCE_ADMIN',        9);
define('TERM_REQUEUE_OWNER',     10);
define('TERM_REQUEUE_ADMIN',     11);
define('TERM_CPULIMIT',          12);
define('TERM_CHKPNT',            13);
define('TERM_OWNER',             14);
define('TERM_ADMIN',             15);
define('TERM_MEMLIMIT',          16);
define('TERM_EXTERNAL_SIGNAL',   17);
define('TERM_RMS',               18);
define('TERM_ZOMBIE',            19);
define('TERM_SWAP',              20);
define('TERM_THREADLIMIT',       21);
define('TERM_SLURM',             22);
define('TERM_BUCKET_KILL',       23);
define('TERM_CTRL_PID',          24);
define('TERM_CWD_NOTEXIST',      25);
define('TERM_REMOVE_HUNG_JOB',   26);
define('TERM_ORPHAN_SYSTEM',     27);
define('TERM_PRE_EXEC_FAIL',     28);
define('TERM_DATA',              29);
define('TERM_MC_RECALL',         30);
define('TERM_RC_RECLAIM',        31);
define('TERM_CSM_ALLOC',         32);
define('TERM_KUBE',              33);
define('TERM_RC',                34);

/* base error messages */
define("LSE_NO_ERR",                       0);  /* initial value */
define("LSE_BAD_XDR",                      1);  /* Error during XDR */
define("LSE_MSG_SYS",                      2);  /* Failed in sending/receiving a msg */
define("LSE_BAD_ARGS",                     3);  /* supplied arguments invalid */
define("LSE_MASTR_UNKNW",                  4);  /* cannot find out the master LIM*/
define("LSE_LIM_DOWN",                     5);  /* LIM does not respond */
define("LSE_PROTOC_LIM",                   6);  /* LIM protocol error */
define("LSE_SOCK_SYS",                     7);  /* A socket operation has failed */
define("LSE_ACCEPT_SYS",                   8);  /* Failed in a accept system call */
define("LSE_BAD_TASKF",                    9);  /* Bad LSF task file format*/
define("LSE_NO_HOST",                     10);  /* No enough ok hosts found by LIM*/
define("LSE_NO_ELHOST",                   11);  /* No host is found eligible by LIM */
define("LSE_TIME_OUT",                    12);  /* communication timed out */
define("LSE_NIOS_DOWN",                   13);  /* nios has not been started. */
define("LSE_LIM_DENIED",                  14);  /* Operation permission denied by LIM */
define("LSE_LIM_IGNORE",                  15);  /* Operation ignored by LIM */
define("LSE_LIM_BADHOST",                 16);  /* host name not recognizable by LIM*/
define("LSE_LIM_ALOCKED",                 17);  /* LIM already locked */
define("LSE_LIM_NLOCKED",                 18);  /* LIM was not locked. */
define("LSE_LIM_BADMOD",                  19);  /* unknown host model. */
define("LSE_SIG_SYS",                     20);  /* A signal related system call failed*/
define("LSE_BAD_EXP",                     21);  /* bad resource req. expression*/
define("LSE_NORCHILD",                    22);  /* no remote child */
define("LSE_MALLOC",                      23);  /* memory allocation failed */
define("LSE_LSFCONF",                     24);  /* unable to open lsf.conf */
define("LSE_BAD_ENV",                     25);  /* bad configuration environment */
define("LSE_LIM_NREG",                    26);  /* Lim is not a registered service*/
define("LSE_RES_NREG",                    27);  /* Res is not a registered service*/
define("LSE_RES_NOMORECONN",              28);  /* RES is serving too many connections*/
define("LSE_BADUSER",                     29);  /* Bad user ID for REX */
define("LSE_RES_ROOTSECURE",              30);  /* Root user rejected */
define("LSE_RES_DENIED",                  31);  /* User permission denied */
define("LSE_BAD_OPCODE",                  32);  /* bad op code */
define("LSE_PROTOC_RES",                  33);  /* RES Protocol error */
define("LSE_RES_CALLBACK",                34);  /* RES callback fails */
define("LSE_RES_NOMEM",                   35);  /* RES malloc fails */
define("LSE_RES_FATAL",                   36);  /* RES system call error */
define("LSE_RES_PTY",                     37);  /* RES cannot alloc pty */
define("LSE_RES_SOCK",                    38);  /* RES socketpair fails */
define("LSE_RES_FORK",                    39);  /* RES fork fails */
define("LSE_NOMORE_SOCK",                 40);  /* Privileged socks run out */
define("LSE_WDIR",                        41);  /* getwd() failed */
define("LSE_LOSTCON",                     42);  /* Connection has been lost */
define("LSE_RES_INVCHILD",                43);  /* No such remote child */
define("LSE_RES_KILL",                    44);  /* Remote kill permission denied */
define("LSE_PTYMODE",                     45);  /* ptymode inconsistency */
define("LSE_BAD_HOST",                    46);  /* Bad hostname */
define("LSE_PROTOC_NIOS",                 47);  /* NIOS protocol error */
define("LSE_WAIT_SYS",                    48);  /* A wait system call failed */
define("LSE_SETPARAM",                    49);  /* Bad parameters for setstdin */
define("LSE_RPIDLISTLEN",                 50);  /* Insufficient list len for rpids */
define("LSE_BAD_CLUSTER",                 51);  /* Invalid cluster name */
define("LSE_RES_VERSION",                 52);  /* Incompatible versions of tty params */
define("LSE_EXECV_SYS",                   53);  /* Failed in a execv() sys call*/
define("LSE_RES_DIR",                     54);  /* No such directory */
define("LSE_RES_DIRW",                    55);  /* The directory may not be accessible*/
define("LSE_BAD_SERVID",                  56);  /* the service ID is invalid */
define("LSE_NLSF_HOST",                   57);  /* request from a non lsf host */
define("LSE_UNKWN_RESNAME",               58);  /* unknown resource name specified */
define("LSE_UNKWN_RESVALUE",              59);  /* unknown resource value */
define("LSE_TASKEXIST",                   60);  /* the task already registered */
define("LSE_BAD_TID",                     61);  /* the task does not exist */
define("LSE_TOOMANYTASK",                 62);  /* the task table is full */
define("LSE_LIMIT_SYS",                   63);  /* A resource limit sys call failed*/
define("LSE_BAD_NAMELIST",                64);  /* bad index name list */
define("LSE_NO_LICENSE",                  65);  /* no software license for host */
define("LSE_LIM_NOMEM",                   66);  /* lim malloc failure */
define("LSE_NIO_INIT",                    67);  /* nio not initialized. */
define("LSE_CONF_SYNTAX",                 68);  /* Bad lsf.conf/lsf.sudoers syntax */
define("LSE_FILE_SYS",                    69);  /* A file operation failed */
define("LSE_CONN_SYS",                    70);  /* A connect sys call failed */
define("LSE_SELECT_SYS",                  71);  /* A select system call failed */
define("LSE_EOF",                         72);  /* Reached the end of file */
define("LSE_ACCT_FORMAT",                 73);  /* Bad lsf.acct file format */
define("LSE_BAD_TIME",                    74);  /* Bad time specification */
define("LSE_FORK",                        75);  /* Unable to fork child */
define("LSE_PIPE",                        76);  /* Failed to setup pipe */
define("LSE_ESUB",                        77);  /* esub/eexec file not found */
define("LSE_DCE_EXEC",                    78);  /* dce task exec fail */
define("LSE_EAUTH",                       79);  /* external authentication failed */
define("LSE_NO_FILE",                     80);  /* cannot open file */
define("LSE_NO_CHAN",                     81);  /* out of communication channels */
define("LSE_BAD_CHAN",                    82);  /* bad communication channel */
define("LSE_INTERNAL",                    83);  /* internal library error */
define("LSE_PROTOCOL",                    84);  /* protocol error with server */
define("LSE_THRD_SYS",                    85);  /* A thread system call failed (NT only)*/
define("LSE_MISC_SYS",                    86);  /* A system call failed */
define("LSE_LOGON_FAIL",                  87);  /* Failed to logon user (NT only) */
define("LSE_RES_RUSAGE",                  88);  /* Failed to get rusage from RES */
define("LSE_NO_RESOURCE",                 89);  /* no shared resource defined */
define("LSE_BAD_RESOURCE",                90);  /* Bad resource name */
define("LSE_RES_PARENT",                  91);  /* res child Failed to contact parent */
define("LSE_NO_PASSWD",                   92);  /* no password for user */
define("LSE_SUDOERS_CONF",                93);  /* lsf.sudoers file error */
define("LSE_SUDOERS_ROOT",                94);  /* lsf.sudoers not owned by root */
define("LSE_I18N_SETLC",                  95);  /* i18n setlocale failed */
define("LSE_I18N_CATOPEN",                96);  /* i18n catopen failed */
define("LSE_I18N_NOMEM",                  97);  /* i18n malloc failed */
define("LSE_NO_MEM",                      98);  /* Cannot alloc memory */
define("LSE_REGISTRY_SYS",                99);  /* A registry system call failed (NT) */
define("LSE_FILE_CLOSE",                 100);  /* Close a NULL-FILE pointer */
define("LSE_LIMCONF_NOTREADY",           101);  /* LIM configuration is not ready yet */
define("LSE_MASTER_LIM_DOWN",            102);  /* for LIM_CONF master LIM down */
define("LSE_MLS_INVALID",                103);  /* invalid MLS label */
define("LSE_MLS_CLEARANCE",              104);  /* not enough clearance */
define("LSE_MLS_RHOST",                  105);  /* reject by rhost.conf */
define("LSE_MLS_DOMINATE",               106);  /* require label not dominate */
define("LSE_NO_CAL",                     107);  /* Win32: No more connections can be
                                                 *  made to this remote computer at this
                                                 *  time because there are already as
                                                 *  many connections as the computer can
                                                 *  accept. */
define("LSE_NO_NETWORK",                 108);  /* Win32: The network location cannot be
                                                 *  reached. For information about
                                                 *  network troubleshooting, see Windows
                                                 *  Help. */
define("LSE_GETCONF_FAILED",             109);  /* Failed to get configuration
                                                 *  information from hosts specified by
                                                 *  the LSF_SERVER_HOSTS parameter in
                                                 *  lsf.conf */
define("LSE_TSSINIT",                    110);  /* Win32: terminal service not properly
                                                 *  initialized */
define("LSE_DYNM_DENIED",                111);  /* Dynamic addHost denied */
define("LSE_LIC_OVERUSE",                112);  /* In license overuse status */
define("LSE_EGOCONF",                    113);  /* unable to open ego.conf */
define("LSE_BAD_EGO_ENV",                114);  /* bad configuration environment */
define("LSE_EGO_CONF_SYNTAX",            115);  /* Bad ego.conf syntax */
define("LSE_EGO_GETCONF_FAILED",         116);  /* Failed to get configuration
                                                 *  information from hosts specified by
                                                 *  the EGO_SERVER_HOSTS parameter in
                                                 *  ego.conf
                                                */
define("LSE_NS_LOOKUP",                  117);  /* name service lookup failure */
define("LSE_BAD_PASSWD",                 118);  /* User password incorrect */

define("LSE_UNKWN_USER",                 119);  /* User name is not in password database */
define("LSE_NOT_WINHOST",                120);  /* The host is not a Windows host */
define("LSE_NOT_MASTERCAND",             121);  /* The host is not a master candidate host */
define("LSE_HOST_UNAUTH",                122);  /* Permission denied. This command must
                                                 *  be issued from a master, master
                                                 *  candidate, or local host */
define("LSE_UNRESOLVALBE_HOST",          123);  /* master Lim can not reslove the host
                                                 *  name of child lim */
define("LSE_RESOURCE_NOT_CONSUMABLE",    124);  /* resource not consumable */
define("LSE_SHUTDOWN",                   125);  /* host is in exiting loop for rejected by clusters */
define("LSE_BAD_SYNTAX",                 126);  /* Bad string syntax */
define("LSE_LIVE_PERSIST",               127);  /* Live reconfig persist failure */
define("LSE_LIVE_FAIL",                  128);  /* Live reconfig exception failure */
define("LSE_BAD_HOST_TYPE",              129);  /* The host type can not be recognized */
define("LSE_INVALID_LICENSE",            130);  /* Invalid license */
define("LSE_NO_ENTITLEMENT",             131);  /* No entitlement found */
define("LSE_SLOTS_IN_RUSAGE",            132);  /* for example, "rsuage[slots=2]" */
define("LSE_INVALID_EXCLAMATION_MARK",   133);  /* for example, "order[!!slots]" */
define("LSE_INVALID_MASTERHOSTS_NUMBER", 134);  /* invalid master hosts number */
define("LSE_REMOVE_JOBVM",               135);  /* Cannot remove Dynamic cluster job container */
define("LSE_INVALID_AFFINITY_RESREQ",    136);  /* Invalid affinity[] resReq */
define("LSE_IP_RESOLVABLE_HOST",         137);  /* Host registered is a server or static client */
define("LSE_FAILED_UPD_REGHOST_DATA",    138);  /* Failed update the host's IP */

define("LSE_HS_BAD_AFTER_BRACKT",        139);  /* An int must follow an open bracket */
define("LSE_HS_NO_END_INDEX",            140);  /* An end index must follow a dash */
define("LSE_HS_BAD_COMMA",               141);  /* Integers must come before and after the comma */
define("LSE_HS_BAD_FORMAT",              142);  /* Incorrect condensed host specification */
define("LSE_HS_BAD_ORDER",               143);  /* The start index must be less than end index */
define("LSE_HS_BAD_MANY_DIGITS",         144);  /* The end index must be less than 10 digits */
define("LSE_HS_BAD_NUM_DIGITS",          145);  /* Number of digits in the start index must be less than that of end index */
define("LSE_HS_BAD_END_INDEX",           146);  /* The end index cannot start with zero (0) */
define("LSE_HS_BAD_INDEX",               147);  /* Index must be an integer or a range */
define("LSE_ASKED_HOSTS_NUMBER",         148);  /* number of hosts specified by -m exceeding configuration */
define("LSE_NO_SPACE",                   149);  /* No space left on device */
define("LSE_NO_GPUINFO",                 150);  /* No gpus topology on host */
define("LSE_NO_CPUBIND_INFO",            151);  /* No gpus topology on host */
define("LSE_GPU_COMPACT_USAGE_ENABLED",  152);  /* GPU compact usage, LSB_GPU_NEW_SYNTAX*/
define("LSE_CTNER_NOT_EXIST",            153);  /* container to attach does not exist */
define("LSE_NERR",                       154);  /* Moving number, size of ls_errmsg[] */

/* important batch error messages */
define("LSBE_NO_ERROR",                                         0);  /* No error at all */
define("LSBE_NO_JOB",                                           1);  /* No matching job found */
define("LSBE_NOT_STARTED",                                      2);  /* Job not started yet */
define("LSBE_JOB_STARTED",                                      3);  /* Job already started */
define("LSBE_JOB_FINISH",                                       4);  /* Job already finished */
define("LSBE_STOP_JOB",                                         5);  /* Ask sbatchd to stop the wrong job */
define("LSBE_DEPEND_SYNTAX",                                    6);  /* Depend_cond syntax error */
define("LSBE_EXCLUSIVE",                                        7);  /* Queue doesn't accept EXCLUSIVE job */
define("LSBE_ROOT",                                             8);  /* Root is not allowed to submit jobs */
define("LSBE_MIGRATION",                                        9);  /* Job is already being migrated */
define("LSBE_J_UNCHKPNTABLE",                                  10);  /* Job is not chkpntable */
define("LSBE_NO_OUTPUT",                                       11);  /* Job has no output so far */
define("LSBE_NO_JOBID",                                        12);  /* No jobId can be used now */
define("LSBE_ONLY_INTERACTIVE",                                13);  /* Queue only accepts bsub -I job */
define("LSBE_NO_INTERACTIVE",                                  14);  /* Queue doesn't accept bsub -I job */

/*  Error codes related to user, queue and host */
define("LSBE_NO_USER",                                         15);  /* No user defined in lsb.users file */
define("LSBE_BAD_USER",                                        16);  /* Bad user name */
define("LSBE_PERMISSION",                                      17);  /* User permission denied */
define("LSBE_BAD_QUEUE",                                       18);  /* No such queue in the system */
define("LSBE_QUEUE_NAME",                                      19);  /* Queue name should be given */
define("LSBE_QUEUE_CLOSED",                                    20);  /* Queue has been closed */
define("LSBE_QUEUE_WINDOW",                                    21);  /* Queue windows are closed */
define("LSBE_QUEUE_USE",                                       22);  /* User cannot use the queue */
define("LSBE_BAD_HOST",                                        23);  /* Bad host name or host group name" */
define("LSBE_PROC_NUM",                                        24);  /* Too many processors requested */
define("LSBE_NO_HPART",                                        25);  /* No host partition in the system */
define("LSBE_BAD_HPART",                                       26);  /* Bad host partition name */
define("LSBE_NO_GROUP",                                        27);  /* No group defined in the system */
define("LSBE_BAD_GROUP",                                       28);  /* Bad host/user group name */
define("LSBE_QUEUE_HOST",                                      29);  /* Host is not used by the queue */
define("LSBE_UJOB_LIMIT",                                      30);  /* User reach UJOB_LIMIT of the queue */
define("LSBE_NO_HOST",                                         31);  /* No host available for migration */

define("LSBE_BAD_CHKLOG",                                      32);  /* chklog is corrupted */
define("LSBE_PJOB_LIMIT",                                      33);  /* User reach PJOB_LIMIT of the queue */
define("LSBE_NOLSF_HOST",                                      34);  /* request from non LSF host rejected*/

/*  Error codes related to input arguments of lsblib call */
define("LSBE_BAD_ARG",                                         35);  /* Bad argument for lsblib call */
define("LSBE_BAD_TIME",                                        36);  /* Bad time spec for lsblib call */
define("LSBE_START_TIME",                                      37);  /* Start time is later than end time */
define("LSBE_BAD_LIMIT",                                       38);  /* Bad CPU limit specification */
define("LSBE_OVER_LIMIT",                                      39);  /* Over hard limit of queue */
define("LSBE_BAD_CMD",                                         40);  /* Empty job (command) */
define("LSBE_BAD_SIGNAL",                                      41);  /* Bad signal value; not supported */
define("LSBE_BAD_JOB",                                         42);  /* Bad job name */
define("LSBE_QJOB_LIMIT",                                      43);  /* Queue reach QJOB_LIMIT of the queue */
define("LSBE_BAD_TERM",                                        44);  /* Expired job terminate time*/
/*  44 is reserved for future use */

/*  Error codes related to lsb.events file */
define("LSBE_UNKNOWN_EVENT",                                   45);  /* Unknown event in event log file */
define("LSBE_EVENT_FORMAT",                                    46);  /* bad event format in event log file */
define("LSBE_EOF",                                             47);  /* End of file */
/*  48-49 are reserved for future use */

/*  Error codes related to system failure */
define("LSBE_MBATCHD",                                         50);  /* mbatchd internal error */
define("LSBE_SBATCHD",                                         51);  /* sbatchd internal error */
define("LSBE_LSBLIB",                                          52);  /* lsbatch lib internal error */
define("LSBE_LSLIB",                                           53);  /* LSLIB call fails */
define("LSBE_SYS_CALL",                                        54);  /* System call fails */
define("LSBE_NO_MEM",                                          55);  /* Cannot alloc memory */
define("LSBE_SERVICE",                                         56);  /* Lsbatch service not registered */
define("LSBE_NO_ENV",                                          57);  /* LSB_SHAREDIR not defined */
define("LSBE_CHKPNT_CALL",                                     58);  /* chkpnt system call fail */
define("LSBE_NO_FORK",                                         59);  /* mbatchd cannot fork */

/*  Error codes related to communication between mbatchd/lsblib/sbatchd */
define("LSBE_PROTOCOL",                                        60);  /* LSBATCH protocol error */
define("LSBE_XDR",                                             61);  /* XDR en/decode error */
define("LSBE_PORT",                                            62);  /* No appropriate port can be bound */
define("LSBE_TIME_OUT",                                        63);  /* Timeout in contacting mbatchd */
define("LSBE_CONN_TIMEOUT",                                    64);  /* Timeout on connect() call */
define("LSBE_CONN_REFUSED",                                    65);  /* Connection refused by server */
define("LSBE_CONN_EXIST",                                      66);  /* server connection already exists */
define("LSBE_CONN_NONEXIST",                                   67);  /* server is not connected */
define("LSBE_SBD_UNREACH",                                     68);  /* sbatchd cannot be reached */
define("LSBE_OP_RETRY",                                        69);  /* Operation cannot be performed right
                                                                   now,  op. will be retried. */
define("LSBE_USER_JLIMIT",                                     70);  /* user has no enough job slots */
/*  71 is reserved for future use */

/*  Error codes related to NQS */
define("LSBE_NQS_BAD_PAR",                                     72);  /* Bad specification for a NQS job */

define("LSBE_NO_LICENSE",                                      73);  /* Client host has no license */

/*  Error codes related to calendar */
define("LSBE_BAD_CALENDAR",                                    74);  /* Bad calendar name */
define("LSBE_NOMATCH_CALENDAR",                                75);  /* No calendar found */
define("LSBE_NO_CALENDAR",                                     76);  /* No calendar in system */
define("LSBE_BAD_TIMEEVENT",                                   77);  /* Bad calendar time events */
define("LSBE_CAL_EXIST",                                       78);  /* Calendar exist already */
define("LSBE_CAL_DISABLED",                                    79);  /* Calendar function is not enabled*/

/*  Error codes related to modify job's parameters */
define("LSBE_JOB_MODIFY",                                      80);  /* the job's params cannot be changed */
define("LSBE_JOB_MODIFY_ONCE",                                 81);  /* the changed once parameters are not used  */

define("LSBE_J_UNREPETITIVE",                                  82);  /* the job is not a repetitive job */
define("LSBE_BAD_CLUSTER",                                     83);  /* bad cluster name */

/*  Error codes related jobs driven by calendar */
define("LSBE_PEND_CAL_JOB",                                    84);  /* Job can not be killed in pending */
define("LSBE_RUN_CAL_JOB",                                     85);  /* This Running turn is being terminated  */

define("LSBE_JOB_MODIFY_USED",                                 86);  /* Modified parameters are being used */
define("LSBE_AFS_TOKENS",                                      87);  /* Can not get user's token */

/*  Error codes related to event */
define("LSBE_BAD_EVENT",                                       88);  /* Bad event name */
define("LSBE_NOMATCH_EVENT",                                   89);  /* No event found */
define("LSBE_NO_EVENT",                                        90);  /* No event in system */

/*  Error codes related to user, queue and host */
define("LSBE_HJOB_LIMIT",                                      91);  /* User reach HJOB_LIMIT of the queue */

/*  Error codes related to bmsg */
define("LSBE_MSG_DELIVERED",                                   92);  /* Message delivered */
define("LSBE_NO_JOBMSG",                                       93);  /* MBD could not find the message that  SBD mentions about */
define("LSBE_MSG_RETRY",                                       94);  /* x */

/*  Error codes related to resource requirement */
define("LSBE_BAD_RESREQ",                                      95);  /* Bad resource requirement */

define("LSBE_NO_ENOUGH_HOST",                                  96);  /* No enough hosts */

/*  Error codes related to configuration lsblib call */
define("LSBE_CONF_FATAL",                                      97);  /* Fatal error in reading conf files */
define("LSBE_CONF_WARNING",                                    98);  /* Warning error in reading conf files */


define("LSBE_CAL_MODIFY",                                      99);  /* CONF used calendar cannot be modified */
define("LSBE_JOB_CAL_MODIFY",                                 100);  /* Job created calendar cannot be modified */
define("LSBE_HP_FAIRSHARE_DEF",                               101);  /* FAIRSHARE queue or HPART  defined */
define("LSBE_NO_RESOURCE",                                    102);  /* No resource specified */
define("LSBE_BAD_RESOURCE",                                   103);  /* Bad resource name */
define("LSBE_INTERACTIVE_CAL",                                104);  /* Calendar not allowed for interactive job  */
define("LSBE_INTERACTIVE_RERUN",                              105);  /* Interactive job cannot be rerunnable  */
define("LSBE_PTY_INFILE",                                     106);  /* PTY and infile specified */
define("LSBE_JS_DISABLED",                                    107);  /* JobScheduler is disabled */

define("LSBE_BAD_SUBMISSION_HOST",                            108);  /* Submission host and its host type can  not be found any more */
define("LSBE_LOCK_JOB",                                       109);  /* Lock the job so that it cann't be resume  by sbatchd */
define("LSBE_UGROUP_MEMBER",                                  110);  /* user not in the user group */
define("LSBE_UNSUPPORTED_MC",                                 111);  /* Operation not supported for a Multicluster  job */
define("LSBE_PERMISSION_MC",                                  112);  /* Operation permission denied for a Multicluster  job */
define("LSBE_SYSCAL_EXIST",                                   113);  /* System Calendar exist already */
define("LSBE_OVER_RUSAGE",                                    114);  /* exceed q's resource reservation */
define("LSBE_BAD_HOST_SPEC",                                  115);  /* bad host spec of run/cpu limits */
define("LSBE_SYNTAX_CALENDAR",                                116);  /* calendar syntax error */
define("LSBE_CAL_USED",                                       117);  /* delete a used calendar */
define("LSBE_CAL_CYC",                                        118);  /* cyclic calednar dependence */
define("LSBE_BAD_UGROUP",                                     119);  /* bad user group name */
define("LSBE_ESUB_ABORT",                                     120);  /* esub aborted request */
define("LSBE_EXCEPT_SYNTAX",                                  121);  /* Bad exception handler syntax */
define("LSBE_EXCEPT_COND",                                    122);  /* Bad exception condition specification  */
define("LSBE_EXCEPT_ACTION",                                  123);  /* Bad or invalid action specification  */
define("LSBE_JOB_DEP",                                        124);  /* job dependence, not deleted immed */
/*  error codes for job group */
define("LSBE_JGRP_EXIST",                                     125);  /* the job group exists */
define("LSBE_JGRP_NULL",                                      126);  /* the job group doesn't exist */
define("LSBE_JGRP_HASJOB",                                    127);  /* the group contains jobs */
define("LSBE_JGRP_CTRL_UNKWN",                                128);  /* the unknown group control signal */
define("LSBE_JGRP_BAD",                                       129);  /* Bad Job Group name */
define("LSBE_JOB_ARRAY",                                      130);  /* Job Array */
define("LSBE_JOB_SUSP",                                       131);  /* Suspended job not supported */
define("LSBE_JOB_FORW",                                       132);  /* Forwarded job not suported */
define("LSBE_JGRP_HOLD",                                      133);  /* parent group is held */
define("LSBE_BAD_IDX",                                        134);  /* bad index */
define("LSBE_BIG_IDX",                                        135);  /* index too big */
define("LSBE_ARRAY_NULL",                                     136);  /* job array not exist*/
define("LSBE_CAL_VOID",                                       137);  /* Void calendar */
define("LSBE_JOB_EXIST",                                      138);  /* the job exists */
define("LSBE_JOB_ELEMENT",                                    139);  /* Job Element fail */
define("LSBE_BAD_JOBID",                                      140);  /* Bad jobId */
define("LSBE_MOD_JOB_NAME",                                   141);  /* cannot change job name */

/*  error codes for frame job */
define("LSBE_BAD_FRAME",                                      142);  /* Bad frame expression */
define("LSBE_FRAME_BIG_IDX",                                  143);  /* Frame index too long */
define("LSBE_FRAME_BAD_IDX",                                  144);  /* Frame index syntax error */

define("LSBE_PREMATURE",                                      145);  /* child process died */

/*  error code for user not in project group */
define("LSBE_BAD_PROJECT_GROUP",                              146);  /* Invoker is not in project group */

/*  error code for user group / host group */
define("LSBE_NO_HOST_GROUP",                                  147);  /* No host group defined in the system */
define("LSBE_NO_USER_GROUP",                                  148);  /* No user group defined in the system */
define("LSBE_INDEX_FORMAT",                                   149);  /* Bad jobid index file format */

/*  error codes for IO_SPOOL facility */
define("LSBE_SP_SRC_NOT_SEEN",                                150);  /* source file does not exist */
define("LSBE_SP_FAILED_HOSTS_LIM",                            151);  /* Number of failed spool hosts reached max */
define("LSBE_SP_COPY_FAILED",                                 152);  /* spool copy failed for this host*/
define("LSBE_SP_FORK_FAILED",                                 153);  /* fork failed */
define("LSBE_SP_CHILD_DIES",                                  154);  /* status of child is not available */
define("LSBE_SP_CHILD_FAILED",                                155);  /* child terminated with failure */
define("LSBE_SP_FIND_HOST_FAILED",                            156);  /* Unable to find a host for spooling */
define("LSBE_SP_SPOOLDIR_FAILED",                             157);  /* Cannot get $JOB_SPOOLDIR for this host */
define("LSBE_SP_DELETE_FAILED",                               158);  /* Cannot delete spool file for this host */

define("LSBE_BAD_USER_PRIORITY",                              159);  /* Bad user priority */
define("LSBE_NO_JOB_PRIORITY",                                160);  /* Job priority control undefined */
define("LSBE_JOB_REQUEUED",                                   161);  /* Job has been killed & requeued */
define("LSBE_JOB_REQUEUE_REMOTE",                             162);  /* Remote job cannot kill-requeued */
define("LSBE_NQS_NO_ARRJOB",                                  163);  /* Cannot submit job array to a NQS queue */

/*  error codes for EXT_JOB_STATUS */
define("LSBE_BAD_EXT_MSGID",                                  164);  /* No message available */
define("LSBE_NO_IFREG",                                       165);  /* Not a regular file */
define("LSBE_BAD_ATTA_DIR",                                   166);  /* MBD fail to create files in the directory*/
define("LSBE_COPY_DATA",                                      167);  /* Fail to transfer data */
define("LSBE_JOB_ATTA_LIMIT",                                 168);  /* exceed the limit on data transferring of a msg*/
define("LSBE_CHUNK_JOB",                                      169);  /* cannot resize a chunk job, cannot bswitch a run/wait job */

/*  Error code used in communications with dlogd */

define("LSBE_DLOGD_ISCONN",                                   170);  /* dlogd is already connected */

/*  Error code for LANL3_1ST_HOST */
define("LSBE_MULTI_FIRST_HOST",                               171);  /* Multiple first execution host */
define("LSBE_HG_FIRST_HOST",                                  172);  /* Host group as first execution host */
define("LSBE_HP_FIRST_HOST",                                  173);  /* Host partition as first execution host */
define("LSBE_OTHERS_FIRST_HOST",                              174);  /* "others" as first execution host */

/*  error code for multi-cluster: remote only queue */
define("LSBE_MC_HOST",                                        175);  /* cannot specify exec host */
define("LSBE_MC_REPETITIVE",                                  176);  /* cannot specify repetitive job */
define("LSBE_MC_CHKPNT",                                      177);  /* cannot be a chkpnt job */
define("LSBE_MC_EXCEPTION",                                   178);  /* cannot specify exception */
define("LSBE_MC_TIMEEVENT",                                   179);  /* cannot specify time event */
define("LSBE_PROC_LESS",                                      180);  /* Too few processors requested */
define("LSBE_MOD_MIX_OPTS",                                   181);  /* bmod pending options and running options  together towards running job */
define("LSBE_MOD_REMOTE",                                     182);  /* cannot bmod remote running job */
define("LSBE_MOD_CPULIMIT",                                   183);  /* cannot bmod cpulimit without LSB_JOB_CPULIMIT  defined */
define("LSBE_MOD_MEMLIMIT",                                   184);  /* cannot bmod memlimit without LSB_JOB_MEMLIMIT  defined */
define("LSBE_MOD_ERRFILE",                                    185);  /* cannot bmod err file name */
define("LSBE_LOCKED_MASTER",                                  186);  /* host is locked by master LIM*/
define("LSBE_WARNING_INVALID_TIME_PERIOD",                    187);  /* warning time period is invalid  */
define("LSBE_WARNING_MISSING",                                188);  /* either warning time period or warning  action is not specified */
define("LSBE_DEP_ARRAY_SIZE",                                 189);  /* The job arrays involved in
                                                                      *  one to one dependency do not
                                                                      *  have the same size.
                                                                     */
define("LSBE_FEWER_PROCS",                                    190);  /* Not enough processors to be reserved (lsb_addreservation()) */
define("LSBE_BAD_RSVID",                                      191);  /* Bad reservation ID */
define("LSBE_NO_RSVID",                                       192);  /* No more reservation IDs can be used now */
define("LSBE_NO_EXPORT_HOST",                                 193);  /* No hosts are exported */
define("LSBE_REMOTE_HOST_CONTROL",                            194);  /* Trying to control remote hosts*/
define("LSBE_REMOTE_CLOSED",                                  195);  /*Can't open a remote host closed by the remote cluster admin */
define("LSBE_USER_SUSPENDED",                                 196);  /* User suspended job */
define("LSBE_ADMIN_SUSPENDED",                                197);  /* Admin suspended job */
define("LSBE_NOT_LOCAL_HOST",                                 198);  /* Not a local host name in
                                                                      *  bhost -e command
                                                                     */
define("LSBE_LEASE_INACTIVE",                                 199);  /* The host's lease is not active. */
define("LSBE_QUEUE_ADRSV",                                    200);  /* The advance reserved host is not on queue. */
define("LSBE_HOST_NOT_EXPORTED",                              201);  /* The specified host(s) is not exported. */
define("LSBE_HOST_ADRSV",                                     202);  /* The user specified host is not inn advance reservation */
define("LSBE_MC_CONN_NONEXIST",                               203);  /* The remote cluster is not connected */
define("LSBE_RL_BREAK",                                       204);  /* The general resource limit broken */

/*  ---- The following RMS errors are obsoleted in Eagle */
define("LSBE_LSF2TP_PREEMPT",                                 205);  /* cannot submit a job with special
                                                                      *  topology requirement to a
                                                                      *  preemptive queue
                                                                     */
define("LSBE_LSF2TP_RESERVE",                                 206);  /* cannot submit a job with special
                                                                      *  topology requirement to a queue
                                                                      *  with slot reservation
                                                                     */
define("LSBE_LSF2TP_BACKFILL",                                207);  /* cannot submit a job with special
                                                                      *  topology requirement to a queue
                                                                      *  with backill
                                                                     */
/*  ---- The above RMS errors are obsoleted in Eagle */
define("LSBE_RSV_POLICY_NAME_BAD",                            208);  /* none existed policy name */
define("LSBE_RSV_POLICY_PERMISSION_DENIED",                   209);  /* All normal user has no privilege */
define("LSBE_RSV_POLICY_USER",                                210);  /* user has no privilege */
define("LSBE_RSV_POLICY_HOST",                                211);  /* user has no privilege to create reservation on host */
define("LSBE_RSV_POLICY_TIMEWINDOW",                          212);  /* time window is not allowed by policy */
define("LSBE_RSV_POLICY_DISABLED",                            213);  /* the feature is disabled */
/*  the general limit related errors */
define("LSBE_LIM_NO_GENERAL_LIMIT",                           214);  /* There are no general limit defined */
define("LSBE_LIM_NO_RSRC_USAGE",                              215);  /* There are no resource usage */
define("LSBE_LIM_CONVERT_ERROR",                              216);  /* Convert data error */
define("LSBE_RSV_NO_HOST",                                    217);  /* There are no qualified host found in cluster*/
define("LSBE_MOD_JGRP_ARRAY",                                 218);  /* Cannot modify job group on element of job array */
define("LSBE_MOD_MIX",                                        219);  /* Cannot combine modify job group or service class option with others */
define("LSBE_SLA_NULL",                                       220);  /* the service class doesn't exist */
define("LSBE_MOD_JGRP_SLA",                                   221);  /* Modify job group for job in service class is not supported*/
define("LSBE_SLA_MEMBER",                                     222);  /* User or user group is not a member of the specified service class */
define("LSBE_NO_EXCEPTIONAL_HOST",                            223);  /* There is no exceptional host found */
define("LSBE_WARNING_INVALID_ACTION",                         224);  /* warning action (signal) is invalid */

define("LSBE_EXTSCHED_SYNTAX",                                225);  /* Extsched option syntax error */
define("LSBE_SLA_RMT_ONLY_QUEUE",                             226);  /* SLA doesn't work with remote only queues */
define("LSBE_MOD_SLA_ARRAY",                                  227);  /* Cannot modify service class on element of job array */
define("LSBE_MOD_SLA_JGRP",                                   228);  /* Modify service class for job in job group is not supported*/
define("LSBE_MAX_PEND",                                       229);  /* Max. Pending job error */
define("LSBE_CONCURRENT",                                     230);  /* System concurrent query exceeded, (also caused by non-threadsafe write to chan, use b_write_fix instead of writeChan_) */
define("LSBE_FEATURE_NULL",                                   231);  /* Requested feature not enabled */

define("LSBE_DYNGRP_MEMBER",                                  232);  /* Host is already member of group */
define("LSBE_BAD_DYN_HOST",                                   233);  /* Host is not a dynamic host */
define("LSBE_NO_GRP_MEMBER",                                  234);  /* Host was not added with badmin hghostadd */
define("LSBE_JOB_INFO_FILE",                                  235);  /* Cannot create job info file */
define("LSBE_MOD_OR_RUSAGE",                                  236);  /* Cannot modify rusage to a new || (or) expression after the job is dispatched */
define("LSBE_BAD_GROUP_NAME",                                 237);  /* Bad host group name */
define("LSBE_BAD_HOST_NAME",                                  238);  /* Bad host name */
define("LSBE_DT_BSUB",                                        239);  /* Bsub is not permitted on DT cluster */

define("LSBE_PARENT_SYM_JOB",                                 240);  /* The parent symphony job/group was
                                                                      *  gone when submitting jobs
                                                                     */
define("LSBE_PARTITION_NO_CPU",                               241);  /* The partition has no cpu alllocated */
define("LSBE_PARTITION_BATCH",                                242);  /* batch partition does not accept online jobs: obsolete */
define("LSBE_PARTITION_ONLINE",                               243);  /* online partition does not accept batch jobs */
define("LSBE_NOLICENSE_BATCH",                                244);  /* no batch licenses */
define("LSBE_NOLICENSE_ONLINE",                               245);  /* no online licenses */
define("LSBE_SIGNAL_SRVJOB",                                  246);  /* signal is not supported for service job */
define("LSBE_BEGIN_TIME_INVALID",                             247);  /* the begin time is not later than current time. */
define("LSBE_END_TIME_INVALID",                               248);  /* the end time is not later than current time. */
define("LSBE_BAD_REG_EXPR",                                   249);  /* Bad regular expression */

define("LSBE_GRP_REG_EXPR",                                   250);  /* Host group has regular expression */
define("LSBE_GRP_HAVE_NO_MEMB",                               251);  /* Host group have no member */
define("LSBE_APP_NULL",                                       252);  /* the application doesn't exist */
define("LSBE_PROC_JOB_APP",                                   253);  /* job's proclimit rejected by App */
define("LSBE_PROC_APP_QUE",                                   254);  /* app's proclimit rejected by Queue */
define("LSBE_BAD_APPNAME",                                    255);  /* application name is too long */
define("LSBE_APP_OVER_LIMIT",                                 256);  /* Over hard limit of queue */
define("LSBE_REMOVE_DEF_APP",                                 257);  /* Cannot remove default application */
define("LSBE_EGO_DISABLED",                                   258);  /* Host is disabled by EGO */
define("LSBE_REMOTE_HOST",                                    259);  /* Host is a remote host. Remote hosts cannot be added to a local host group. */
define("LSBE_SLA_EXCLUSIVE",                                  260);  /* SLA is exclusive, only accept exclusive job. */
define("LSBE_SLA_NONEXCLUSIVE",                               261);  /* SLA is non-exclusive, only accept non-exclusive job */
define("LSBE_PERFMON_STARTED",                                262);  /* The feature has already been started */
define("LSBE_PERFMON_STOPED",                                 263);  /* The Featurn has already been turn down */
define("LSBE_PERFMON_PERIOD_SET",                             264);  /* Current sampling period is already set to %%s,seconds. Ignored*/
define("LSBE_DEFAULT_SPOOL_DIR_DISABLED",                     265);  /* Default spool dir is disabled */
define("LSBE_APS_QUEUE_JOB",                                  266);  /* job belongs to an APS queue and cannot be moved */
define("LSBE_BAD_APS_JOB",                                    267);  /* job is not in an absolute priority enabled queue */
define("LSBE_BAD_APS_VAL",                                    268);  /* Wrong aps admin value */
define("LSBE_APS_STRING_UNDEF",                               269);  /* Trying to delete a non-existent APS string */
define("LSBE_SLA_JOB_APS_QUEUE",                              270);  /* A job cannot be assigned an SLA and an APS queue with factor FS */
define("LSBE_MOD_MIX_APS",                                    271);  /* bmod -aps | -apsn option cannot be mixed with other option */
define("LSBE_APS_RANGE",                                      272);  /* specified ADMIN factor/system APS value out of range */
define("LSBE_APS_ZERO",                                       273);  /* specified ADMIN factor/system APS value is zero */

define("LSBE_DJOB_RES_PORT_UNKNOWN",                          274);  /* res port is unknown */
define("LSBE_DJOB_RES_TIMEOUT",                               275);  /* timeout on res communication */
define("LSBE_DJOB_RES_IOERR",                                 276);  /* I/O error on remote stream */
define("LSBE_DJOB_RES_INTERNAL_FAILURE",                      277);  /* res internal failure */

define("LSBE_DJOB_CAN_NOT_RUN",                               278);  /* can not run outside LSF */
define("LSBE_DJOB_VALIDATION_BAD_JOBID",                      279);  /* distributed job's validation failed due to incorrect job ID or index */
define("LSBE_DJOB_VALIDATION_BAD_HOST",                       280);  /* distributed job's validation failed due to incorrect host selection */
define("LSBE_DJOB_VALIDATION_BAD_USER",                       281);  /* distributed job's validation failed due to incorrect user */
define("LSBE_DJOB_EXECUTE_TASK",                              282);  /* failed while executing tasks */
define("LSBE_DJOB_WAIT_TASK",                                 283);  /* failed while waiting for tasks to finish*/

define("LSBE_APS_HPC",                                        284);  /* Obsoleted error: HPC License not exist */
define("LSBE_DIGEST_CHECK_BSUB",                              285);  /* Integrity check of bsub command failed */
define("LSBE_DJOB_DISABLED",                                  286);  /* Distributed Application Framework disabled */

/*  Error codes related to runtime estimation and cwd */
define("LSBE_BAD_RUNTIME",                                    287);  /* Bad runtime specification */
define("LSBE_BAD_RUNLIMIT",                                   288);  /* RUNLIMIT: Cannot exceed RUNTIME*JOB_RUNLIMIT_RATIO */
define("LSBE_OVER_QUEUE_LIMIT",                               289);  /* RUNTIME: Cannot exceed the hard runtime limit in the queue */
define("LSBE_SET_BY_RATIO",                                   290);  /* RUNLIMIT: Is not set by command line */
define("LSBE_BAD_CWD",                                        291);  /* current working directory name too long */

define("LSBE_JGRP_LIMIT_GRTR_THAN_PARENT",                    292);  /* Job group limit is greater than its parent group */
define("LSBE_JGRP_LIMIT_LESS_THAN_CHILDREN",                  293);  /* Job group limit is less than its children groups */
define("LSBE_NO_ARRAY_END_INDEX",                             294);  /* Job Array end index should be specified explicitly */
define("LSBE_MOD_RUNTIME",                                    295);  /* cannot bmod runtime without LSB_MOD_ALL_JOBS=y defined */

/*  EP3 */
define("LSBE_BAD_SUCCESS_EXIT_VALUES",                        296);
define("LSBE_DUP_SUCCESS_EXIT_VALUES",                        297);
define("LSBE_NO_SUCCESS_EXIT_VALUES",                         298);

define("LSBE_JOB_REQUEUE_BADARG",                             299);
define("LSBE_JOB_REQUEUE_DUPLICATED",                         300);
define("LSBE_JOB_REQUEUE_INVALID_DIGIT",                      301);  /* "all" with number */
define("LSBE_JOB_REQUEUE_INVALID_TILDE",                      302);  /* ~digit without "all" */
define("LSBE_JOB_REQUEUE_NOVALID",                            303);

define("LSBE_NO_JGRP",                                        304);  /* No matching job group found */
define("LSBE_NOT_CONSUMABLE",                                 305);

/*  AR pre/post */
define("LSBE_RSV_BAD_EXEC",                                   306);  /* Cannot parse an Advance Reservation -exec string */
define("LSBE_RSV_EVENTTYPE",                                  307);  /* Unknown AR event type */
define("LSBE_RSV_SHIFT",                                      308);  /* pre/post cannot have postive offset */
define("LSBE_RSV_USHIFT",                                     309);  /* pre-AR command cannot have offset < 0 in user-created AR */
define("LSBE_RSV_NUMEVENTS",                                  310);  /* only one pre- and one post- cmd permitted per AR */

/*Error  codes related to AR Modification*/
define("LSBE_ADRSV_ID_VALID",                                 311);  /* ID does not correspond to a known AR. */
define("LSBE_ADRSV_DISABLE_NONRECUR",                         312);  /* disable non-recurrent AR. */
define("LSBE_ADRSV_MOD_ACTINSTANCE",                          313);  /* modification is rejected because AR is activated. */
define("LSBE_ADRSV_HOST_NOTAVAIL",                            314);  /* modification is rejected because host slots is not available. */
define("LSBE_ADRSV_TIME_MOD_FAIL",                            315);  /* the time of the AR cannot be modified since resource is not available. */
define("LSBE_ADRSV_R_AND_N",                                  316);  /* resource requirement (-R) must be followed a slot requirment (-n) */
define("LSBE_ADRSV_EMPTY",                                    317);  /*modification is rejected because trying to empty the AR. */
define("LSBE_ADRSV_SWITCHTYPE",                               318);  /*modification is rejected because switching AR type. */
define("LSBE_ADRSV_SYS_N",                                    319);  /*modification is rejected because specifying -n for system AR. */
define("LSBE_ADRSV_DISABLE",                                  320);  /* disable string is not valid. */
define("LSBE_ADRSV_ID_UNIQUE",                                321);  /* Unique AR ID required */
define("LSBE_BAD_RSVNAME",                                    322);  /* Bad reservation name */
define("LSBE_ADVRSV_ACTIVESTART",                             323);  /* Cannot change the start time of an active reservation. */
define("LSBE_ADRSV_ID_USED",                                  324);  /* AR ID is refernced by a job */
define("LSBE_ADRSV_PREVDISABLED",                             325);  /* the disable period has already been disabled */
define("LSBE_ADRSV_DISABLECURR",                              326);  /* an active period of a recurring reservation cannot be disabled */
define("LSBE_ADRSV_NOT_RSV_HOST",                             327);  /* modification is rejected because specified hosts or host groups do not belong to the reservation */

/*new  parser */
define("LSBE_RESREQ_OK",                                      328);  /*checking resreq return ok */
define("LSBE_RESREQ_ERR",                                     329);  /*checking resreq return error */

define("LSBE_ADRSV_HOST_USED",                                330);  /* modification is rejected because reservation has running jobs on the specified hosts or host groups */

define("LSBE_BAD_CHKPNTDIR",                                  331);  /* The checkpoint directory is too long */
define("LSBE_ADRSV_MOD_REMOTE",                               332);  /* trying to modify in a remote cluster */
define("LSBE_JOB_REQUEUE_BADEXCLUDE",                         333);
define("LSBE_ADRSV_DISABLE_DATE",                             334);  /* trying to disable for a date in the past */
define("LSBE_ADRSV_DETACH_MIX",                               335);  /* cannot mix the -Un option with others for started jobs */
define("LSBE_ADRSV_DETACH_ACTIVE",                            336);  /* cannot detach a started job when the reservation is active */
define("LSBE_MISSING_START_END_TIME",                         337);  /* invalid time expression: must specify day for both start and end time */
define("LSBE_JOB_RUSAGE_EXCEED_LIMIT",                        338);  /* Queue level limitation */
define("LSBE_APP_RUSAGE_EXCEED_LIMIT",                        339);  /* Queue level limitation */
define("LSBE_CANDIDATE_HOST_EMPTY",                           340);  /* Hosts and host groups specified by -m are not used by the queue */
define("LSBE_HS_BAD_AFTER_BRACKT",                            341);  /* An int must follow an open bracket */
define("LSBE_HS_NO_END_INDEX",                                342);  /* An end index must follow a dash */
define("LSBE_HS_BAD_COMMA",                                   343);  /* Integers must come before and after the comma */
define("LSBE_HS_BAD_FORMAT",                                  344);  /* Incorrect condensed host specification */
define("LSBE_HS_BAD_ORDER",                                   345);  /* The start index must be less than end index */
define("LSBE_HS_BAD_MANY_DIGITS",                             346);  /* The end index must be less than 10 digits */
define("LSBE_HS_BAD_NUM_DIGITS",                              347);  /* Number of digits in the start index must be less than that of end index */
define("LSBE_HS_BAD_END_INDEX",                               348);  /* The end index cannot start with zero (0) */
define("LSBE_HS_BAD_INDEX",                                   349);  /* Index must be an integer or a range */

/*  host group admin*/
define("LSBE_COMMENTS",                                       350);  /* When a Host Group Admin (badmin hclose or hopen) closes or opens a host,
                                                                      *  the usage of the -C "message" option must be compulsory, as is the logging
                                                                      *  of the name of the person performing the action. */

define("LSBE_FIRST_HOSTS_NOT_IN_QUEUE",                       351);  /* First hosts specified by -m are not used by the queue */

define("LSBE_JOB_NOTSTART",                                   352);  /* The job is not started */
define("LSBE_RUNTIME_INVAL",                                  353);  /* Accumulated runtime of the job is not available */
define("LSBE_SSH_NOT_INTERACTIVE",                            354);  /* SSH feature can only be used for interactive job */
define("LSBE_LESS_RUNTIME",                                   355);  /* Run time specification is less than the accumulated run time */
define("LSBE_RESIZE_NOTIFY_CMD_LEN",                          356);  /* Resize job notification command */
define("LSBE_JOB_RESIZABLE",                                  357);  /* Job is not resizable */
define("LSBE_RESIZE_RELEASE_HOSTSPEC",                        358);  /* Bad bresize release host spec */
define("LSBE_NO_RESIZE_NOTIFY",                               359);  /* no resize notify matches in mbatchd*/
define("LSBE_RESIZE_RELEASE_FRISTHOST",                       360);  /* Can't release first exec host */
define("LSBE_RESIZE_EVENT_INPROGRESS",                        361);  /* resize event in progress */
define("LSBE_RESIZE_BAD_SLOTS",                               362);  /* too few or too many slots */
define("LSBE_RESIZE_NO_ACTIVE_REQUEST",                       363);  /* No active resize request */
define("LSBE_HOST_NOT_IN_ALLOC",                              364);  /* specified host not part of the
                                                                      *  job's allocation
                                                                     */
define("LSBE_RESIZE_RELEASE_NOOP",                            365);  /* nothing released */
define("LSBE_RESIZE_URGENT_JOB",                              366);  /* Can't resize a brun job */
define("LSBE_RESIZE_EGO_SLA_COEXIST",                         367);
define("LSBE_HOST_NOT_SUPPORT_RESIZE",                        368);  /* hpc jobs can't be resized */
define("LSBE_APP_RESIZABLE",                                  369);  /* Application doesn't allow resizable */
define("LSBE_RESIZE_LOST_AND_FOUND",                          370);  /* can't operate on lost & found
                                                                      *  hosts
                                                                     */
define("LSBE_RESIZE_FIRSTHOST_LOST_AND_FOUND",                371);  /* can't resize while the
                                                                      *  first host is lost
                                                                      *  & found
                                                                     */
define("LSBE_RESIZE_BAD_HOST",                                372);  /* bad host name (for resize) */
define("LSBE_AUTORESIZE_APP",                                 373);  /* proper app is required by an auto-resizable job */
define("LSBE_RESIZE_PENDING_REQUEST",                         374);  /* cannot resize job because there is a pedning resize request */
define("LSBE_ASKED_HOSTS_NUMBER",                             375);  /* number of hosts specified by -m exceeding configuration */
define("LSBE_AR_HOST_EMPTY",                                  376);  /* All hosts reserved by advanced reservation are invalid in intersected hosts */
define("LSBE_AR_FIRST_HOST_EMPTY",                            377);  /* First hosts specified by -m are not used by advanced reservation */
define("LSBE_JB",                                             378);  /* Internal jobbroker error */
define("LSBE_JB_DBLIB",                                       379);  /* Internal jobbroker database library error */
define("LSBE_JB_DB_UNREACH",                                  380);  /* Jobbroker cannot reach database */
define("LSBE_JB_MBD_UNREACH",                                 381);  /* Jobbroker cannot reach mbatchd */
define("LSBE_JB_BES",                                         382);  /* BES server returned an error */
define("LSBE_JB_BES_UNSUPPORTED_OP",                          383);  /* Unsupported BES operation */
define("LSBE_LS_PROJECT_NAME",                                384);  /* invalid LS project name*/
define("LSBE_END_TIME_INVALID_COMPARE_START",                 385);  /* the end time is not later than start  time. */
define("LSBE_HP_REDUNDANT_HOST",                              386);  /* one host cannot be defined in more than one host partition.*/
define("LSBE_COMPOUND_APP_SLOTS",                             387);  /* The application level compound resreq causes slots requirements conflict */
define("LSBE_COMPOUND_QUEUE_SLOTS",                           388);  /* The queue level compound resreq causes slots requirements conflict */
define("LSBE_ADV_RSRCREQ_RESIZE",                             389);  /* Resizable job cannot work with
                                                                      *  compound or alternative resreq
                                                                     */
define("LSBE_COMPOUND_RESIZE",            LSBE_ADV_RSRCREQ_RESIZE);  /* obsolete */
/*  compute unit support */
define("LSBE_CU_OVERLAPPING_HOST",                            390);  /* Compute units cannot have overlapping hosts */
define("LSBE_CU_BAD_HOST",                                    391);  /* The compute unit cannot contain other compute units */
define("LSBE_CU_HOST_NOT_ALLOWED",                            392);  /* The compute unit cannot contain host or host group as a member */
define("LSBE_CU_NOT_LOWEST_LEVEL",                            393);  /* Only lowest level compute units are allowed to add hosts as a member */
define("LSBE_CU_MOD_RESREQ",                                  394);  /* You cannot modify a compute unit resource requirement when a job is already running */
define("LSBE_CU_AUTORESIZE",                                  395);  /* OBSOLETE ** A compute unit resource requirement cannot be specified for auto resizable jobs */
define("LSBE_NO_COMPUTE_UNIT_TYPES",                          396);  /* No COMPUTE_UNIT_TYPES are specified in lsb.params */
define("LSBE_NO_COMPUTE_UNIT",                                397);  /* No compute unit defined in the system */
define("LSBE_BAD_COMPUTE_UNIT",                               398);  /* No such compute unit defined in the system */
define("LSBE_CU_EXCLUSIVE",                                   399);  /* The queue is not configured to accept exclusive compute unit jobs */
define("LSBE_CU_EXCLUSIVE_LEVEL",                             400);  /* The queue is not configured to accept higher level of exclusive compute unit jobs */
define("LSBE_CU_SWITCH",                                      401);  /* Job cannot be switched due to the exclusive compute unit reqirement */
define("LSBE_COMPOUND_JOB_SLOTS",                             402);  /* Job level compound resreq causes slots requirements conflict */
define("LSBE_COMPOUND_QUEUE_RUSAGE_OR",                       403);  /* "||" used in rusage[] of queue resource requirement. It's conflict with job level compound resource requirement */
define("LSBE_CU_BALANCE_USABLECUSLOTS",                       404);  /* balance and usablecuslots cannot both be used in a compute unit resource requirement */
define("LSBE_COMPOUND_TSJOB_APP",                             405);  /* TS jobs cannot use compound resource requirement (application level) */
define("LSBE_COMPOUND_TSJOB_QUEUE",                           406);  /* TS jobs cannot use compound resource requirement (queue level) */
define("LSBE_EXCEED_MAX_JOB_NAME_DEP",                        407);  /* Job dependency conditions using a job name or job name wild-card exceed  limitation set by MAX_JOB_NAME_DEP in lsb.params */
define("LSBE_WAIT_FOR_MC_SYNC",                               408);  /* "is waiting for the remote cluster to synchronize." */
define("LSBE_RUSAGE_EXCEED_RESRSV_LIMIT",                     409);  /* Job cannot exceed queue level RESRSV_LIMIT limitation */
define("LSBE_JOB_DESCRIPTION_LEN",                            410);  /* job description too long */
define("LSBE_NOT_IN_SIMMODE",                                 411);  /* Cannot use simulation options */
define("LSBE_SIM_OPT_RUNTIME",                                412);  /* Value of runtime simulation is incorrect */
define("LSBE_SIM_OPT_CPUTIME",                                413);  /* Value of cputime simulation is incorrect */
define("LSBE_SIM_OPT_MAXMEM",                                 414);  /* Incorrect maxmem simulation opt */
define("LSBE_SIM_OPT_EXITSTATUS",                             415);  /* Incorrect job exitstatus simulation opt */
define("LSBE_SIM_OPT_SYNTAX",                                 416);  /* Incorrect job simulation option syntax */
define("LSBE_SIM_BSUB",                                       417);  /*Can not submission job withot -sim*/
define("LSBE_MAX_SLOTS_IN_POOL",                              418);  /* MAX_SLOTS_IN_POOL is reached. */

define("LSBE_BAD_GUAR_RESOURCE_NAME",                         419);  /* Guaranteed Resource pool
                                                                      *  name does not exist
                                                                     */
define("LSBE_GUAR_SLA_USER_NOT_ALLOWED",                      420);  /* User and guarantee sla
                                                                      *  mismatch
                                                                     */
define("LSBE_GUAR_SLA_QUEUE_NOT_ALLOWED",                     421);  /* Queue and guarantee sla
                                                                      *  mismatch
                                                                     */
define("LSBE_GUAR_SLA_APP_NOT_ALLOWED",                       422);  /* App profile and guarantee sla
                                                                      *  mismatch
                                                                     */
define("LSBE_GUAR_SLA_PROJECT_NOT_ALLOWED",                   423);  /* Project and guarantee sla
                                                                      *  mismatch
                                                                     */
define("LSBE_GUAR_SLA_GROUP_NOT_ALLOWED",                     424);  /* User fairshare group and
                                                                      *  guarantee sla mismatch
                                                                     */
define("LSBE_GUAR_SLA_GROUP_QUEUE_MISMATCH",                  425);  /* Group queue fairshare
                                                                      *  problem
                                                                     */
define("LSBE_SLA_NOT_GUAR_SLA",                               426);  /* Specified sla not a
                                                                      *  guarantee sla
                                                                     */
define("LSBE_SLA_ACCESS_CONTROL",                             427);  /* Does not satisfy guarantee
                                                                      *  sla access control
                                                                     */
define("LSBE_GUAR_SLA_JOB_STARTED",                           428);  /* Can't mod started job belonging
                                                                      *  to guarantee SLA
                                                                     */
define("LSBE_GUAR_SLA_INVALID_OP",                            429);  /* Operation not allowed for
                                                                      *  guarantee type SLAs
                                                                     */

define("LSBE_LIVECONF_MBD_RETERR",                            430);  /* Live Reconfig got error message from mbatchd*/
define("LSBE_LIVECONF_MBD_REJECT",                            431);  /* Live Reconfig got error message from mbatchd*/
define("LSBE_LIVECONF_MBD_INFO",                              432);  /* Live Reconfig got info message from mbatchd*/
define("LSBE_EXCEED_JOBSPERPACK_LIMITATION",                  433);  /* The amount of job requests in one pack exceeds the  limit LSB_MAX_PACK_JOBS defined in lsf.conf */
define("LSBE_PACK_SUBMISSION_DISABLED",                       434);  /* Pack submission is disabled by defining LSB_MAX_PACK_JOBS=0 in lsf.conf */
define("LSBE_GUAR_SLA_LP_NOT_ALLOWED",                        435);  /* License project and guarantee sla mismatch */
define("LSBE_GLB_MIX_MODE",                                   436);  /* Job requests both cluster mode and a project mode License Scheduler resources */
define("LSBE_PERFLOG_ENABLED_ALREADY",                        437);  /* Performance metrics logging is already enabled */

define("LSBE_GE_UNSUPPORT_CMD",                               439);  /* This command is not supported on grid execution cluster */
define("LSBE_WRONG_DEST_QUEUE",                               440);  /* The destination queue does not include all send queue of source queue */
define("LSBE_MULTI_HOST",                                     441);  /* Multiple hosts with same hostname exist */
define("LSBE_MULTI_HOST_SPEC",                                442);  /* Multiple hosts with same hostname exist in hostSpec */
define("LSBE_RUN_JOB_PROCESSING",                             443);  /* brun job is in processing, block other job control cmd*/
define("LSBE_GBRUNJOB_LEASEHOST",                             444);  /* cannot brun job on lease host in gb mode*/
define("LSBE_GBRUNJOB_BAD_HOST",                              445);  /* The specified hosts in brun should be all local hosts or all remote hosts in one cluster */
define("LSBE_LOCAL_CLUSTER",                                  446);  /* bsub -m localcluster */
define("LSBE_OTHER_HOST",                                     447);  /* bsub -m others or bsub -m "rmthost others" */
define("LSBE_GBRUNJOB_CLUSTER_NOTSUPPORT",                    448);  /* brun -m clustername is not supported. */
define("LSBE_MOD_BAD_CLUSTER_HOST",                           449);  /* Fore forwarded job, all bmod -m hosts don't belong execution cluster of the job */
define("LSBE_NO_LSFXL_ENTITLEMENT",                           450);  /* XL requires LSF Advanced Edition */
define("LSBE_CLUSTER_FIRST_HOST",                             451);  /* "cluster" as first execution host */
define("LSBE_HOST_FORMAT",                                    452);  /* bad askedHost format */
define("LSBE_MBD_IN_RESTART",                                 453);  /* mbatchd is in process of restart */
define("LSBE_MBD_PRESTART_DUPLOG",                            454);  /* Does not support parallel restart under dup event log*/
define("LSBE_EOB",                                            455);  /* encounter boundary */
define("LSBE_WIN_PRESTART_NOT_SUPPORT",                       456);  /* do not support parallel restart */
define("LSBE_QUEUE_HOST2",                                    457);  /* host, hostgroup or cluster not use by queue */
define("LSBE_MOD_PROCESSOR",                                  458);  /* cannot change processor num of forwarded job */
define("LSBE_HOST_HOSTGROUP_INVALID",                         459);  /* Host name or host group name is not valid */
define("LSBE_BQUEUES_HOST_NOT_SUPPORT",                       460);  /* Remote host or hostgroup cannot be used in bqueues -m option */
define("LSBE_XL_SAME_CLUSTER",                                461);  /* For brun forward-only case, cannot brun  the forwarded job to the same cluster  on which it is pend */
define("LSBE_RUN_FORWARD_MIX",                                462);  /* -m cluster_name cannot be used with other  brun optons */
define("LSBE_JOBSPECIFY_ERROR",                               463);  /* when the jobs -sum a invalid */
define("LSBE_SND_RCV_QUEUE",                                  464);  /* in XL brun force-forward, when the send-recv  queue configuration is not  correct */
define("LSBE_BRUN_DEST_CLUSTER",                              465);  /* in XL brun force-forward, when job's  asked hosts not belong to the specified  cluster */
define("LSBE_JOB_FORWARD_CANCELLING",                         466);  /* Job is during unforward operation, some other operation should be blocked. */
define("LSBE_BRUN_COMPOUND_RESREQ",                           467);  /* In XL, cannot brun a job with compound resource
                                                                      *  requirement to remote cluster. */
define("LSBE_JOB_REQUEUING",                                  468);  /* Job is during brequeue operation, some other operation should be blocked. */
define("LSBE_BRUN_ONE_CLUSTER",                               469);  /* Only one name is allowed if specify cluster name */
define("LSBE_QUERY_DIAGNOSE_DIR_ERROR",                       470);  /* The directory for logdir does not exist or has no write permission. */
define("LSBE_QUERY_DIAGNOSE_DURATION_ERROR",                  471);  /* The duration for query diagnosis is not valid. */
define("LSBE_JOB_BEING_KILL",                                 472);  /* job is "bkill", but not receive the status  update from execution cluster, block  brun*/
define("LSBE_EMB_BSUB",                                       473);  /* This command cannot be embedded within bsub. */
define("LSBE_QUERY_UPD_JOBCREATE",                            474);  /* Create new job failed in query MBD */
define("LSBE_QUERY_UPD_NOTENABLE",                            475);  /* new job updating thread is not enabled */
define("LSBE_REMOTE_HOST_UNSUPPORT",                          476);  /* Remote host is not supported */
define("LSBE_AC_RESREQ_COMPOUND",                             477);  /* Dynamic Cluster job cannot use compound or alternative resource requirement */
define("LSBE_AC_RESREQ_MULTIPHASE",                           478);  /* Dynamic Cluster job cannot use multiphase memory reservation */
define("LSBE_AC_RESREQ_DURATION",                             479);  /* Dynamic Cluster job cannot use duration in memory reservation */
define("LSBE_AC_BAD_HOST",                                    480);  /* The hypervisor hosts you specified do not exist. */
define("LSBE_AC_NO_HOST",                                     481);  /* No hosts in the cluster. */
define("LSBE_AC_BAD_PROVISION_ID",                            482);  /* Incorrect provision ID */
define("LSBE_AC_BAD_PROVISION_JOBID",                         483);  /* Incorrect provision Job ID */
define("LSBE_AC_NO_PROVISION",                                484);  /* No provision requests in the cluster */
define("LSBE_AC_NO_VM",                                       485);  /* No hosts in the cluster */
define("LSBE_AC_BAD_VM",                                      486);  /* The virtual machines you specified do not exist */
define("LSBE_AC_NO_TEMPLATE",                                 487);  /* No Dynamic Cluster template requests found */
define("LSBE_AC_NO_SUIT_TEMPLATE",                            488);  /* No Dynamic Cluster templates found in the cluster */
define("LSBE_AC_TEMPLATE_NOT_FOUND",                          489);  /* The Dynamic Cluster template you specified does not exist */
define("LSBE_AC_NO_PARAMETERS",                               490);  /* Incorrect parameter */
define("LSBE_AC_HOST_IN_PROVISIONING",                        491);  /* Cannot operate on hosts in the Provisioning state */
define("LSBE_AC_HOST_IN_SAVED",                               492);  /* Cannot operate on hosts in the Saved state */
define("LSBE_AC_HOST_SBD_STARTING_UP",                        493);  /* Cannot operate on hosts waiting for the server batch daemon to start up */
define("LSBE_AC_RESIZABLE",                                   494);  /* Dynamic Cluster jobs cannot be resizable jobs */
define("LSBE_AC_VIRTUAL_MACHINE",                             495);  /* Cannot specify virtual machines */
define("LSBE_AC_CHUNK_JOB",                                   496);  /* Dynamic Cluster jobs cannot be chunk jobs */
define("LSBE_AC_VMACTION",                                    497);  /* Cannot combine dc_vmaction with Dynamic Cluster physical machine jobs */
define("LSBE_AC_MODIFY_RUNNING_VMJOB",                        498);  /* Cannot modify the Dynamic Cluster job because it is running on a virtual machine */
define("LSBE_AC_MODIFY_VMJOB_IN_SAVED",                       499);  /* Cannot modify the Dynamic Cluster jobs because the job is waiting for a virtual machine to be restored */
define("LSBE_AC_MODIFY_TO_NON_ACJOB",                         500);  /* Cannot modify the Dynamic Cluster job to be a non-Dynamic Cluster job after the job is running */
define("LSBE_AC_JOB_RUSAGE_EXCEED_LIMIT",                     501);  /* Error in the rusage section: The job-level resource requirement (mem) round-up value exceeds the limit set by the queue-level resource requirement (mem) value */
define("LSBE_AC_APP_RUSAGE_EXCEED_LIMIT",                     502);  /* Error in the rusage section: The application-level resource requirement (mem) round-up value exceeds the limit set by the queue-level resource requirement (mem) value */
define("LSBE_AC_DEFAULT_RUSAGE_EXCEED_RESRSV_LIMIT",          503);  /* The default memory requirement value (DC_DEFAULT_JOB_MEMSIZE) is outside the range set by RESRSV_LIMIT defined in lsb.queues */
define("LSBE_AC_JOB_RUSAGE_EXCEED_LARGEST_MEMSIZE",           504);  /* Error in the rusage section: The job-level resource requirement (mem) value exceeds the largest memory size in DC_VM_MEMSIZE_DEFINED */
define("LSBE_AC_APP_RUSAGE_EXCEED_LARGEST_MEMSIZE",           505);  /* Error in the rusage section: The application-level resource requirement (mem) value exceeds the largest memory size in DC_VM_MEMSIZE_DEFINED */
define("LSBE_AC_QUE_RUSAGE_EXCEED_LARGEST_MEMSIZE",           506);  /* Error in the rusage section: The queue-level resource requirement (mem) value exceeds the largest memory size in DC_VM_MEMSIZE_DEFINED */
define("LSBE_AC_NOT_AN_AC_JOB",                               507);  /* Not a Dynamic Cluster job */
define("LSBE_AC_NO_TEMPLATE_SPECIFIED",                       508);  /* No template specified */
define("LSBE_AC_MODIFY_RUNNING_JOB_TO_ACJOB",                 509);  /* Cannot modify the non-Dynamic Cluster job to be a Dynamic Cluster job after the job is running */
define("LSBE_AC_MODIFY_OPTIONS_RUNNING_ACJOB",                510);  /* Cannot modify Dynamic Cluster job options after the job is running */
define("LSBE_AC_MODIFY_OPTIONS_WITHOUT_AC_TMPL",              511);  /* Dynamic Cluster template name was not requested at the job level */
define("LSBE_AC_NOT_AN_AC_VMJOB",                             512);  /* Not a Dynamic Cluster vm job */
define("LSBE_AC_FEATURE_NOT_ENABLE",                          513);  /* Dynamic Cluster is not enabled */

/*  brun under Dynamic Cluster */
define("LSBE_AC_ONE_VM_ONLY_ALLOWED",                         514);  /* The virtual machines cannot be specified with other machine types */
define("LSBE_AC_ONE_HV_ONLY_ALLOWED",                         515);  /* The pure hypervisors cannot be specified with other machine types */
define("LSBE_AC_JOB_IS_SAVED",                                516);  /* Cannot operate on the job because it is already saved into a disk image */
define("LSBE_AC_NON_ACHOST",                                  517);  /* Cannot operate on non-Dynamic Cluster hosts */
define("LSBE_AC_RESOTRE_ON_NON_HV",                           518);  /* Cannot restore the virtual machine on a non-hypervisor host */
define("LSBE_AC_RESOTRE_ON_RESGROUP",                         519);  /* Cannot restore the virtual machine due to different resource group */
define("LSBE_AC_INSUFFICIENT_HOST",                           520);  /* Cannot restore the virtual machine due to insufficient memory or number of CPUs */
define("LSBE_AC_RESTORE_VM_NOT_FOUND",                        521);  /* Cannot restore the virtual machine because the virtual machine is not found */
define("LSBE_AC_UNABLE_TO_RESTORE_VM",                        522);  /* Unable to restore the virtual machine */
define("LSBE_AC_VM_HAS_WORKINFO",                             523);  /* Other workload is on the virtual machine */
define("LSBE_AC_PURE_HYPERVISOR",                             524);  /* Cannot specify the pure hypervisor */
define("LSBE_AC_UNABLE_PROVISION",                            525);  /* Unable to provision the physical or virtual machine */
define("LSBE_AC_HOSTS_MIXED",                                 526);  /* Cannot specify Dynamic Cluster hosts together with non-Dynamic Cluster hosts */

define("LSBE_QUERY_INVALID_EAUTH",                            527);  /* Eauth is invalid for query mbatchd, wait new query mbatchd startup*/
define("LSBE_NOT_REQUEUE_LPJ",                                528);  /* The reply code is describe that this job is local pend state and is not allowed to be requeued. */
define("LSBE_UNSUPPORTED_MC_CHKPNTABLE",                      529);  /* Operation not supported for a Multicluster checkpointable job */
define("LSBE_AC_BAD_VMACTION",                                530);  /* Incorrect virtual machine preemption action syntax */
define("LSBE_AC_SWITCH_MIGRATING_JOB",                        531);  /* Dynamic Cluster jobs cannot be switched while being migrated */
define("LSBE_MOD_COMPOUND_RESREQ",                            532);  /* In XL, cannot modify a forwarded
                                                                      *  job's resource requirement to
                                                                      *  compound resource requirement.
                                                                     */
define("LSBE_ALTERNATIVE_JOB_SLOTS",                          533);  /* Job level alternative resreq
                                                                      *  causes slots requirements conflict
                                                                     */
define("LSBE_ALTERNATIVE_APP_SLOTS",                          534);  /* The application level alternative
                                                                      *  resreq causes slots requirements
                                                                      *  conflict
                                                                     */
define("LSBE_ALTERNATIVE_QUEUE_SLOTS",                        535);  /* The queue level alternative resreq
                                                                      *  causes slots requirements conflict
                                                                     */
define("LSBE_ALTERNATIVE_TSJOB_APP",                          536);  /* TS jobs cannot use alternative
                                                                      *  resource requirement
                                                                      *  (application level)
                                                                     */
define("LSBE_ALTERNATIVE_TSJOB_QUEUE",                        537);  /* TS jobs cannot use alternative
                                                                      *  resource requirement (queue level)
                                                                     */
define("LSBE_ALTERNATIVE_QUEUE_RUSAGE_OR",                    538);  /* "||" used in rusage[] of queue
                                                                      *  resource requirement. It's
                                                                      *  conflict with job level
                                                                      *  alternative resource
                                                                      *  requirement
                                                                     */
define("LSBE_BRUN_ALTERNATIVE_RESREQ",                        539);  /* In XL, cannot brun a job with
                                                                      *  alternative resource
                                                                      *  requirement to remote cluster
                                                                     */
define("LSBE_RESIZE_QUEUE_ADV_RSRCREQ",                       540);  /* Resizable job can't be sent
                                                                      *  to a queue with compound
                                                                      *  or alternative rsrcreq
                                                                     */
define("LSBE_JOB_BEING_MODIFIED",                             541);  /* Job is locked while being modified, other job control
                                                                      *  command cannot be performed */
define("LSBE_MC_BMOD_NOT_SUPPORT",                            542);  /* Remote cluster doesn't support MC bmod */
define("LSBE_MC_BMOD_PARAMETER_DISALLOW",                     543);  /* Parameter is not allowed to be modified on forwarded
                                                                      *  job */
define("LSBE_MC_REMOTE_HOST_SPEC",                            544);  /* Cannot get host spec for remote host */
define("LSBE_EXCLUDED_PRIO",                                  545);  /* specified '~' with preference */
define("LSBE_ALL_OTHERS",                                     546);  /* specified "all" with "others" */
define("LSBE_EXCLUDED_NO_ALL",                                547);  /* specified '~' without "all" */
define("LSBE_ASKED_CLUSTER_NULL",                             548);  /* no valid cluster in specified clusters */
define("LSBE_M_CLUSTER",                                      549);  /* specified -m and -cluster */
define("LSBE_DUPLICATE_CLUSTER",                              550);  /* specified duplicated cluster names */
define("LSBE_CLUSTER_ADRSV",                                  551);  /* AR not in specified cluster*/
define("LSBE_BAD_ASKED_CLUSTER",                              552);  /* Bad asked cluster */
define("LSBE_QUEUE_ASKED_CLUSTER",                            553);  /* Asked cluster is not used by the queue */
define("LSBE_OTHERS_ALONE",                                   554);  /* others can not be used alone */
define("LSBE_GB_ASKED_CLUSTER",                               555);  /* XL mode do not support -cluster option */
define("LSBE_BMOD_ASKED_CLUSTER_DISALLOW",                    556);  /* bmod -cluster/clustern only support local pending job */
define("LSBE_LOCAL_CLUSTER_NONE",                             557);  /* only specify local cluster and HOSTS = none */
define("LSBE_EFFECTIVE_MODIFY_COMPOUND",                      558);  /* Cannot modify job effective
                                                                      *  resource requirement to
                                                                      *  compound resource requirement
                                                                     */
define("LSBE_EFFECTIVE_MODIFY_ALTERNATIVE",                   559);  /* Cannot modify job effective
                                                                      *  resource requirement to alternative
                                                                      *  resource requirement
                                                                     */
define("LSBE_EFFECTIVE_MODIFY_COMPOUND_TO_SIMPLE",            560);  /*  Cannot modify job compound
                                                                      *  effective rsrcreq to simple
                                                                      *  resource requirement
                                                                     */
define("LSBE_MC_MOD_ASKED_HOST",                              561);  /* Cannot modify execuiton host specification for forwarded job */
define("LSBE_MC_MOD_QUEUE",                                   562);  /* Cannot modify queue for a forwarded job. */
define("LSBE_MC_MOD_DEPEND_COND",                             563);  /* Cannot modify dependency condition for a forwarded job. */
define("LSBE_MC_MOD_SLA",                                     564);  /* Cannot modify service class for a forwarded job. */
define("LSBE_LS_NO_RESREQ",                                   565);  /* There is no LS resource sepcified
                                                                      *  in rusage
                                                                     */
define("LSBE_GBRUNJOB_MASTERCANDIDATE",                       566);  /* XL master or master candidate host cannot run job.
                                                                     */
define("LSBE_GB_BRUN_REMOTE_QUEUE",                           567);  /* remote queue cannot receive job */
define("LSBE_GLB_MIX_PROJECT_MODE",                           568);  /* Job requests both fast dispatch project mode and a project mode License Scheduler resources */
define("LSBE_MC_REMOTE_CLUSTER",                              569);  /* cannot specify remote cluster name with -m option in Multi-Cluster or Single-Cluster mode */
define("LSBE_EXCEED_QUEUE_RUSAGE",                            570);  /* Resource requirement values for the job must be within the limits set by the queue-level resource requirement values */
define("LSBE_EXCEED_RESRSV_LIMIT",                            571);  /* The rusage value for the job is outside the range set by RESRSV_LIMIT in lsb.queues */
define("LSBE_AC_SPAN_MANY_VMS",                               572);  /* More than one virtual machine cannot be requested */
define("LSBE_AC_NONE_COEXIST",                                573);  /* Cannot specify the keyword "none" with Dynamic Cluster template names */
define("LSBE_AC_NO_TEMPLATE_VM",                              574);  /* The Dynamic Cluster templates you specified cannot provision the virtual machines */
define("LSBE_AC_NO_TEMPLATE_PM",                              575);  /* The Dynamic Cluster templates you specified cannot provision the physical machines */
define("LSBE_AC_ANY_TMPL_COEXIST",                            576);  /* Cannot specify the keyword "any" with Dynamic Cluster template names */
define("LSBE_AC_NONE_TMPL_WITH_OTHERS",                       577);  /* Other Dynamic Cluster options cannot be combined when the Dynamic Cluster template name "none" is specified */
define("LSBE_AC_TMPL_BEING_REMOVED",                          578);  /* Other Dynamic Cluster options cannot be combined when the Dynamic Cluster template name is being removed */
define("LSBE_AC_JOB_NO_TEMPLATE_VM",                          579);  /* The Dynamic Cluster job's templates cannot provision the virtual machines */
define("LSBE_AC_JOB_NO_TEMPLATE_PM",                          580);  /* The Dynamic Cluster job's templates cannot provision the physical machines */
define("LSBE_AC_JOB_NO_VMACTION",                             581);  /* The preemption action for the virtual machine was not requested at the job level */
define("LSBE_AC_MODIFY_MEM_RUSAGE",                           582);  /* Cannot modify the Dynamic Cluster job resource requirement after the job is running or the job is saved */
define("LSBE_NETWORK_PE_DISABLED",                            583);  /* LSF_PE_NETWORK_NUM is not configured */
define("LSBE_NETWORK_SYNTAX",                                 584);  /* Bad network requirement syntax */
define("LSBE_NETWORK_INVALID_TYPE",                           585);  /* Invalid type in network requirement */
define("LSBE_NETWORK_INVALID_PROTOCOL",                       586);  /* Invaid protocol name in network requirement */
define("LSBE_NETWORK_IP_INSTANCE",                            587);  /* Cannot specify instance with POE job in IP mode*/
define("LSBE_NETWORK_INVALID_USAGE",                          588);  /* Network usage is not valid in network requirement */
define("LSBE_NETWORK_INSTANCE",                               589);  /* Window instance is not valid in network requirement */
define("LSBE_NETWORK_BAD_KEYWORD",                            590);  /* Bad keyword in network requirement */
define("LSBE_EXCEED_MAX_PROTOCOL_CNT",                        591);  /* Specified protocol count for network requirement exceeds the  maximum value of 128 */
define("LSBE_NETWORK_INVALID_MODE",                           592);  /* Mode is not valid in network requirement */
define("LSBE_NETWORK_IP_DEDICATED",                           593);  /* Network dedicated usage cannot be used together with IP mode */
define("LSBE_BRUN_NETWORK_JOB",                               594);  /* Cannot force a job with network requirement to run */
define("LSBE_NETWORK_CHANGE_RUNNING_JOB",                     595);  /* Cannot change running job's network requirement */
define("LSBE_NETWORK_JOB_RESIZE",                             596);  /* cannot resize job with network requirement */
define("LSBE_NETWORK_EXCEED_QUEUE_MAX_PROTOCOL_INSTANCE",     597);  /* Cannot exceed MAX_PROTOCOL_INSTANCES */
define("LSBE_EXCLUSIVE_AFFINITY",                             598);  /* Queue doesn't accept "exclusive=(...,alljobs..." affinity job */
define("LSBE_RESIZE_AFFINITY_JOB_BAD_HOST",                   599);  /* Invalid bresize request for affinity job */
define("LSBE_NETWORK_CHKPNT",                                 600);  /* POE job cannot be checkpointable */
define("LSBE_NETWORK_EXCEED_CLUSTER_MAX_PROTOCOL_INSTANCE",   601);  /* cannt exceed cluster MAX_PROTOCOL_INSTANCES */
define("LSBE_BAD_GLOBAL_FAIRSHARE_NAME",                      602);  /* Global fairshare
                                                                      *  name does not exist
                                                                     */
define("LSBE_UNKNOWN_CLUSTER_NAME",                           603);  /* Global fairshare participant
                                                                      *  cluster does not exist
                                                                     */
define("LSBE_NO_GLOBAL_FAIRSHARE",                            604);  /* No global fairshare defined in lsb.globalpolicies file */
define("LSBE_NOT_GPD_CLUSTER",                                605);  /* The cluster is not GPD cluster */
define("LSBE_NOT_MASTER_CANDIDATE",                           606);  /* The host is not a master candidate host */
define("LSBE_BAD_GPD_CLUSTER",                                607);  /* Unknown GPD cluster name */

/*  RFC1503&2753: Sick Host Part B */
define("LSBE_ZOMBIE_JOB",                                     608);  /* Job can be found but only in the zombie list*/
define("LSBE_AC_CHKPNT_NOT_RERUNNABLE",                       609);  /* DC virtual machine checkpoint job must be rerunnable*/
define("LSBE_AC_CHKPNT_LSFCHKPNT",                            610);  /* DC virtual machine checkpoint job can not be regular checkpointable*/
define("LSBE_AC_CHKPNT_NOT_VMJOB",                            611);  /* DC virtual machine checkpoint job must be VM job*/
define("LSBE_AC_CHKPNT_BAD_ARGUMENT",                         612);  /* Bad DC checkpoint period argument*/
define("LSBE_PE_INVALID_PROTOCOL_COUNT",                      613);  /* Protocol count is not valid in network requirement */
define("LSBE_POWER_NOT_SERVER_HOST",                          614);  /* Not server only host */
define("LSBE_POWER_NO_COMMAND",                               615);  /* No command configured for the power operation */
define("LSBE_POWER_PENDING_OPERTION",                         616);  /* previous power operation is not finished */
define("LSBE_POWER_TATUS_NOT_OK",                             617);  /* Cannot perform requested power action on the host in its current status */
define("LSBE_POWER_NOT_IDLE_HOST",                            618);  /* Host has unfinished job(s) */
define("LSBE_POWER_MASTER_HOST_REJECT",                       619);  /* Cannot power control master or master candidate hosts */

/*  EAS feature */
define("LSBE_MANAGE_FREQUENCY_DISABLED",                      620);  /* CPU frequency management is disabled. Job cannot request a CPU frequency */
define("LSBE_FREQUENCY_EXECLUSIVE",                           621);  /* You specified a CPU frequency. The job must be submitted to an EXCLUSIVE host */
define("LSBE_FREQUENCY_BY_CORE",                              622);  /* LSF_MANAGE_FREQUENCY=CORE is enabled in lsf.conf. Job must be submitted with affinity resource requirements */
define("LSBE_RESIZE_RELEASE_EAS_JOB",                         623);  /* bresize CPU Frequency/Energy collection job should release all */
define("LSBE_UNPENG_FREQUENCY",                               624);  /* Can not modify CPU Frequency for running job */
define("LSBE_POWER_CLOSED_HOST",                              625);  /* Can not run job in power closed host */
define("LSBE_AUTORESIZE_EAS_JOB",                             626);  /* CPU Frequency/Energy collection job does not automatically resize. */
define("LSBE_HOST_LIMIT",                                     627);  /* Host limit is exceed */
define("LSBE_CONCURRENT_QUERY",                               628);  /* System concurrent query exceeded */
define("LSBE_REPEATED_HOST_SKIP",                             629);  /* Skip the repeated host error message */
define("LSBE_EAS_SWITCH_PROV_JOB",                            630);  /* Prov jobs cannot be switched */
define("LSBE_JOB_SIZE_LIST",                                  631);  /* Queue level Job size list is not matched */
define("LSBE_TAG_NAME",                                       632);  /* The maximum length of the energy policy name is 256 characters and valid charters includes: a ~ z, A ~ Z, 0 ~ 9, _ */
define("LSBE_RESIZE_RELEASE_EAS_PREDICTION_JOB",              633);  /* EAS job with frequency prediction cannot be resized */
define("LSBE_AC_PERMISSION_LIVEMIG",                          634);  /* User request denied. Manual live migration is restricted to administrators */
define("LSBE_AC_BAD_VMACTION_LIVEMIG",                        635);  /* Incorrect virtual machine action syntax for a manual live migration */
define("LSBE_AC_VM_WAITING_PREEMPTION_ACTION",                636);  /* Cannot manually live-migrate a job while a preemption action is in progress */
define("LSBE_AC_VM_WAITING_MANUAL_LIVEMIG_ACTION",            637);  /* Cannot manually
                                                                      *  live-migrate a job
                                                                      *  while another manual
                                                                      *  live migration is in
                                                                      *  progress
                                                                     */
define("LSBE_NOTMATCH_NBLOCK",                                638);  /* Block size in resource
                                                                      *  requirement cannot satisfy
                                                                      *  requested slots or slot list
                                                                     */
define("LSBE_GLB_GRIDBROKER",                                 639);  /* Gridbroker does not execute LS
                                                                      *  jobs who request cluster mode or
                                                                      *  new project mode features
                                                                     */
define("LSBE_NLIST_NPROC",                                    640);  /* bsub/bmod/job can't use
                                                                      *  both -nlist and -n options
                                                                     */
define("LSBE_EAS_DB_INITIALIZE",                              641);  /* Failed to initialize EAS MySQL DB */
define("LSBE_EAS_QUERY_ENERGY_DATA_FROM_DB",                  642);  /* Failed to query energy data from EAS MySQL DB */
define("LSBE_UNSUPPORT_EAS_PREDICTION_AUTO_FREQUENCY",        643);  /* Energy Aware Scheduling features - benchmarking, prediction, and automatic select CPU frequency not enabled. */
define("LSBE_EAS_REMOVE_ENERGY_DATA_FROM_DB",                 644);  /* Failed to remove energy data from EAS MySQL DB */
define("LSBE_EAS_INITIALIZE",                                 645);  /* Failed to initialize EAS */
define("LSBE_JOB_MODIFY_RUNNING_EAS_JOB",                     646);  /* Cannot modify energy policy tag or energy policy for running jobs with auto-select CPU frequency */
define("LSBE_EAS_TAG_JOB_UNCHKPNTABLE",                       647);  /* Cannot checkpoint eas job with energy policy "create". */
define("LSBE_EAS_GENERATE_OR_AUTOMATIC_FREQUENCY_EXECLUSIVE", 648);  /* Generate Energy Policy Tag/automatic select CPU Frequency job should submit to an EXCLUSIVE host. */
define("LSBE_ACL_CONTROL",                                    649);  /* No jobs found within your access control level */
define("LSBE_JOB_PROV",                                       650);  /* job in prov */
define("LSBE_EAS_GENERATE_TAG_AUTH",                          651);  /* Cannot create energy policy tag for another user */
define("LSBE_NOT_DEPENDENT_JOB",                              652);  /* modification is rejected because the job is not a dependent job */
define("LSBE_USER_HOST_FILE_ACCESS",                          653);  /* User specified host file is not accessible */
define("LSBE_USER_HOST_FILE_FORMAT",                          654);  /* User specified host file format is incorrect */
define("LSBE_RESIZE_HOST_FILE_JOB_BAD_HOST",                  655);  /* Unable to complete bresize request for the job with user specified host file. bresize release must be performed on all slots of the host */
define("LSBE_COMPOUND_RESREQ_HOST_FILE",                      656);  /* Cannot combine -hostfile option with -R COMPOUND_RESREQ */
define("LSBE_CAND_HOST_NPROC_HOST_FILE",                      657);  /* Cannot combine -hostfile option with -n or -m options */
define("LSBE_ADVRSV_BAD_UNIT",                                658);  /* Bad advance reservation unit specification */
define("LSBE_ADRSV_RM_ALL_USER",                              659);  /* An advance reservation must have at least one user or group */
define("LSBE_FEWER_HOSTS",                                    660);  /* Not enough hosts in the specified time window */
define("LSBE_ADRSV_HOST_CANNOT_FULL_RESERVE",                 661);  /* Host(s) cannot be entirely reserved in the specified time window */
define("LSBE_ADRSV_MOD_USER_FROM_GROUP",                      662);  /* Reservation was created for group and not support multiple  user/group */
define("LSBE_ADRSV_NOT_RSV_USER",                             663);  /* None of the specified users or groups belongs to the reservation.  Cannot modify the reservation. */
define("LSBE_ADRSV_RM_ALL_HOSTS",                             664);  /* An advance reservation must have at least one host. Cannot  remove requested host." */
define("LSBE_ADRSV_M_R_EMPTY",                                665);  /* None of the specified hosts meet the requested resource  requirement. */
define("LSBE_RESIZE_RELEASE_FRISTHOST_4SLOTS",                666);  /* Can't release first exec host */
define("LSBE_RESIZE_BAD_SLOTS_4SLOTS",                        667);  /* too few or too many slots */
define("LSBE_RESIZE_RELEASE_NOOP_4SLOTS",                     668);  /* nothing released */
define("LSBE_CU_BALANCE_USABLECUSLOTS_4SLOTS",                669);  /* balance and usablecuslots cannot both be used in a compute unit resource requirement */
define("LSBE_ALTERNATIVE_JOB_SLOTS_4SLOTS",                   670);  /* Job level alternative resreq
                                                                      *  causes slots requirements conflict
                                                                     */
define("LSBE_ALTERNATIVE_APP_SLOTS_4SLOTS",                   671);  /* The application level alternative
                                                                      *  resreq causes slots requirements
                                                                      *  conflict
                                                                     */
define("LSBE_ALTERNATIVE_QUEUE_SLOTS_4SLOTS",                 672);  /* The queue level alternative resreq
                                                                      *  causes slots requirements conflict
                                                                     */
define("LSBE_NOTMATCH_NBLOCK_4SLOTS",                         673);  /* Block size in resource
                                                                      *  requirement cannot satisfy
                                                                      *  requested slots or slot list
                                                                     */
define("LSBE_PROC_NUM_4SLOTS",                                674);  /* Too many processors requested */
define("LSBE_PROC_LESS_4SLOTS",                               675);  /* Too few processors requested */
define("LSBE_MOD_PROCESSOR_4SLOTS",                           676);  /* cannot change processor num of forwarded job */
define("LSBE_JOB_SIZE_LIST_APP",                              677);  /* application profile level Job size list is not matched */
define("LSBE_NLIST_NPROC_4SLOTS",                             678);  /* This error code is obsoleted and can be re-used */

define("LSBE_BAD_ARG_FOR_ENV",                                679);  /* job is rejected because the bad argument for option -env */

define("LSBE_PROC_JOB_APP_4SLOTS",                            680);  /* job's proclimit rejected by App */
define("LSBE_COMPOUND_APP_SLOTS_4SLOTS",                      681);  /* The application level compound resreq causes slots requirements conflict */
define("LSBE_COMPOUND_QUEUE_SLOTS_4SLOTS",                    682);  /* The queue level compound resreq causes slots requirements conflict */
define("LSBE_COMPOUND_JOB_SLOTS_4SLOTS",                      683);  /* Job level compound resreq causes slots requirements conflict */
define("LSBE_PROC_APP_QUE_4SLOTS",                            684);  /* app's proclimit rejected by Queue */
define("LSBE_CAND_HOST_NPROC_HOST_FILE_4SLOTS",               685);  /* Cannot combine -hostfile option with -n or -m options */

define("LSBE_AC_LIVEMIGVM",                                   686);  /* Cannot combine dc_livemigvm with Dynamic Cluster physical machine jobs */
define("LSBE_AC_SWITCH_DEFRAG_JOB",                           687);  /* Cannot switch queue for a Dynamic Cluster job with a host defragmentation in progress */
define("LSBE_AC_MOD_QUEUE_DEFRAG_JOB",                        688);  /* Cannot modify queue for a Dynamic Cluster job with a host defragmentation in progress */

define("LSBE_DATA_SPEC_FILE_NOT_SPECIFIED",                   689);  /* Data specification file
                                                                      *  not specified
                                                                     */
define("LSBE_DATA_MIXED",                                     690);  /* Can't specify both -data
                                                                      *  and -datan at the same time
                                                                     */
define("LSBE_DATA_SPEC_FILE_EMPTY",                           691);  /* Data specification file
                                                                      *  contained no data
                                                                     */
define("LSBE_DATA_READING_FILE",                              692);  /* There was a problem while
                                                                      *  reading the data specification
                                                                      *  file
                                                                     */
define("LSBE_DATA_CANT_OPEN_FILE",                            693);  /* There was a problem opening
                                                                      *  the data specification file
                                                                     */
define("LSBE_DATA_SIGNATURE_FAIL",                            694);  /* Couldn't determine the
                                                                      *  signature for an entry in the
                                                                      *  data specification file
                                                                     */
define("LSBE_DATA_PATH_FAIL",                                 695);  /* Couldn't determine the file
                                                                      *  for an entry in the data
                                                                      *  specification file
                                                                     */
define("LSBE_DATA_FEATURE_NOT_ENABLE",                        696);  /* Data Aware Scheduling not
                                                                      *  Enabled
                                                                     */
define("LSBE_DATA_FORMAT",                                    697);  /* Incorrect format for an entry
                                                                      *  in the data specification file
                                                                     */
define("LSBE_DATA_SPEC_BAD_HOST_NAME",                        698);  /* Bad host name for an entry in
                                                                      *  the data specification file
                                                                     */
define("LSBE_DATA_CHECKPOINT_MIXED",                          699);  /* A job cannot have both data requirement
                                                                      *  and checkpoint options specified at the same time
                                                                     */
define("LSBE_DATA_NOT_TRANSFER_JOB",                          700);  /* Non data transfer job not
                                                                      *  allowed to use data transfer
                                                                      *  queue
                                                                     */
define("LSBE_DATA_NOT_TRANSFER_QUEUE",                        701);  /* Data transfer job must use
                                                                      *  data transfer queue
                                                                     */
define("LSBE_DATA_CANT_BRUN",                                 702);  /* Can't force job with data
                                                                      *  requirement to run.
                                                                      *  1) Pending job with
                                                                      *  outstanding data staging.
                                                                      *  2) Finished job.
                                                                     */
define("LSBE_DATA_PATH_CANT_ACCESS",                          703);  /* Can't access a file listed
                                                                      *  in the data specification
                                                                      *  file
                                                                     */
define("LSBE_DATA_TRANSFER_JOB_UNSUPPORTED",                  704);  /* Oppearation not permitted
                                                                      *  on data transfer job
                                                                     */
define("LSBE_DATA_SPACE_IN_PATH",                             705);  /* Space in path/file name is not
                                                                      *  supported as data specification
                                                                      *  or data requirement file.
                                                                     */
define("LSBE_DATA_SIZE_NOT_NUMERIC",                          706);  /* Size field specified in data
                                                                      *  specification file must be a
                                                                      *  numeric value.
                                                                     */
define("LSBE_FILE_NOT_EXIST",                                 707);  /* Cannot access a file listed within the data requirement */

define("LSBE_DATA_RELATIVE_IN_PATH",                          708);  /* -data option requires an absolute path */

define("LSBE_DATA_NONE_PROCESSED",                            709);  /* Not even one file processes from -data
                                                                      *  option arguments */

define("LSBE_DATA_INCORRECT_WILDCARD",                        710);  /* Incorrect use of a wild card operator */

define("LSBE_DATA_INCORRECT_FORMAT",                          711);  /* Incorrect character in data file path */

define("LSBE_DATA_INCORRECT_JOBARRAYINDEX",                   712);  /* Incorrect use of the job
                                                                      *  array index '%' character */

define("LSBE_DATA_TAG_DATA_COMBINE",                          713);  /* Can't combine both tag and
                                                                      *  regular data req */

define("LSBE_SYMLINK_AS_DATA",                                714);  /*Not allowed to use symbolic link for local */

define("LSBE_PATH_DIR_FOR_REMOTE",                            715);  /*Not allow to use dir path for remote*/

define("LSBE_WILD_CARD_FOR_REMOTE",                           716);  /*Wild card can not apply to remote host*/

define("LSBE_UNSUPPORTED_FILE_TYPE",                          717);  /*unsupported file type*/

define("LSBE_DAS_PLATFORM_NOT_SUPPORTTED",                    718);  /* Job submission with data requirement is not supportted on this platform */

define("LSBE_UNSUPPORTED_SIGNATURE_SYNTAX",                   719);  /*signature can only be specified to single file*/

define("LSBE_DATA_GRP_NEEDS_REQ",                             720);  /* -datagrp option requires -data also */
define("LSBE_DATA_NOT_DIR",                                   721);  /* Not a directory */
define("LSBE_AC_USER_HOST_FILE",                              722);  /* User-specified host file cannot include Dynamic Cluster job requirements */
define("LSBE_AC_HOST_IN_USER_HOST_FILE",                      723);  /* User-specified host file cannot include Dynamic Cluster hosts or virtual machines */
define("LSBE_J_UNCHKPNTABLE_UNRERUN",                         724);  /* Job is not chkpntable or rerunnable */
define("LSBE_TI_NO_DEPENDENCY",                               725);  /* Cannot specify -ti without -w */
define("LSBE_EXTSCHED_HOST_FILE",                             726);  /* Cannot combine -hostfile option with -ext/-extsched */
define("LSBE_DATA_BAD_HOST_NAME",                             727);  /* Host name not valid */
define("LSBE_DATA_EMPTY_HOST_NAME",                           728);  /* Host name not specified */
define("LSBE_DATA_NO_TRANSFER_QUEUE",                         729);  /* No DATA_TRANSFER queue is configured in the cluster */
define("LSBE_CANNOT_ACCESS_FILE",                             730);  /* Cannot access the file */
define("LSBE_FILE_NAME_TOO_LONG",                             731);  /* The file name is too long */
define("LSBE_MBSCHD_NOT_READY",                               732);  /* Cannot run the \"badmin diagnose -c jobreq\" command because mbschd is not ready */
define("LSBE_PREVIOUS_JOBREQ_IN_PROGRESS",                    733);  /* A previous \"badmin diagnose -c jobreq\" command is already running */
define("LSBE_DATA_GRP_MIXED",                                 734);  /* Cannot use -datagrp and -datagrpn
                                                                      *  at the same time
                                                                     */
define("LSBE_DATA_USER_GROUP_NOT_EXIST",                      735);  /* The -datagrp does not exist
                                                                     */
define("LSBE_DATA_USER_NOT_IN_USER_GROUP",                    736);  /* Job's owner not in -datagrp
                                                                     */
define("LSBE_DATA_USER_GROUP_TAG_COMBINE",                    737);  /* Not allowed to combine tag
                                                                      *  requirement with datagrp
                                                                     */
define("LSBE_RESIZE_RELEASE_HOST_MIDDLE_ALLOC",               738);  /* host can only be released
                                                                      *  from the end of the
                                                                      *  allocation
                                                                     */
define("LSBE_ALTERNATIVE_RSRCREQ_RESIZE",                     739);  /* Resizable job cannot work with
                                                                      *  alternative resreq. This will
                                                                      *  change in the future.
                                                                     */
define("LSBE_EPTL_NO_TRACKELIGIBLEPENDINFO",                  740);  /* Cannot use -eptl when
                                                                      *  TRACKELIGIBLEPENDINFO
                                                                      *  disabled */
define("LSBE_PEND_TIME_LIMIT_FOR_REMOTE",                     741);  /* Cannot modify -ptl|-eptl from
                                                                      *  execution side*/

define("LSBE_MC_RESREQ_REJECT",                               742);  /* Cannot modify -R if resreq size is longer than 511 and
                                                                      *  remote cluster veriosn is less 10.1 */
define("LSBE_DELETE_OLD_STREAM_FILE",                         743);  /* When the number of stream file reach MAX_EVENT_STREAM_FILE_NUMBER,
                                                                      *  LSF will delete the oldest file */
define("LSBE_BMODZ_EAGAIN",                                   744);  /* attempt to modify joinfo cache job during
                                                                      *  event dump or parallel restart. */
define("LSBE_OVERLAPPED_IDX",                                 745);  /* Array job with overlapped index will be rejected */

define("LSBE_MAX_ACTIVE_JOBS",                                746);
define("LSBE_CU_EXCL_BALANCE",                                747);  /* CU with excl or balance specification is
                                                                      *  not supported in compound/alternative
                                                                      *  resource requirement */
define("LSBE_RC_CLOSED",                                      748);  /*Can't open a borrowed host closed by the resource connector plugin */
define("LSBE_BWAIT_TIMEOUT",                                  749);  /* Bwait command timeout */
define("LSBE_BWAIT_SYNTAX",                                   750);  /* Wait condition syntax error */
define("LSBE_BWAIT_INVALID",                                  751);  /* Wait condition is never satisfied */
define("LSBE_EXCEED_MAX_JOB_NAME_BWAIT",                      752);  /* Wait condition using a job name
                                                                      *  or job name wild-card exceed
                                                                      *  limitation set by
                                                                      *  MAX_JOB_NAME_DEP in lsb.params
                                                                     */
define("LSBE_UNKOWN_ERR",                                     753);  /* Unknown error occurs */
define("LSBE_RESIZE_REQUEST",                                 754);  /* bresize request is not enabled */
define("LSBE_GPU_SYNTAX",                                     755);  /* Bad gpu requirement syntax */
define("LSBE_GPU_NUM",                                        756);  /* Gpu num not valid in gpurequirement */
define("LSBE_GPU_INVALID_MODE",                               757);  /* Invalid mode in gpu requirement */
define("LSBE_GPU_INVALID_MPS",                                758);  /* Invalid value for mps switch in gpu requirement */
define("LSBE_GPU_COMPACT_USAGE_ENABLED",                      759);
define("LSBE_GPU_COMPACT_USAGE_DISABLED",                     760);
define("LSBE_MC_GPU_COMPACT_USAGE_ENABLED",                   761);
define("LSBE_MC_GPU_COMPACT_USAGE_DISABLED",                  762);
define("LSBE_GPU_INVALID_JOB_EXCLUSIVE",                      763);
define("LSBE_MAX_PEND_SLOTS",                                 764);  /* Pending job slots threshold reached */
define("LSBE_RESIZE_REQUEST_COMPOUND",                        765);  /* A job with a compound resource requirement
                                                                      *  cannot grow after releasing resources
                                                                      *  corresponding to any of its resource
                                                                      *  requirement terms except for the last term.
                                                                     */
define("LSBE_BY_PROXY_ONLY",                                  766);  /* query requests can only come through
                                                                      *  the proxy daemon
                                                                     */
define("LSBE_PROXY_UNREACHABLE",                              767);  /* The LSF proxy is unreachable.
                                                                     */
define("LSBE_PROXY_INTERNAL",                                 768);  /* proxy internal error
                                                                     */
define("LSBE_BAD_SYMBOLIC_LINK",                              769);  /* encountered a bad symbolic link
                                                                     */
define("LSBE_DATA_NOT_FILE",                                  770);  /* specified data requirement is not
                                                                      *  a file
                                                                     */
define("LSBE_DIR_NOT_EXIST",                                  771);  /* the specified folder doesn't
                                                                      *  exist
                                                                     */
define("LSBE_DATA_FOLDER_NO_ACCESS",                          772);  /* can't access the folder or its
                                                                      *  contents
                                                                     */
define("LSBE_ADRSV_SUSPFLAG_USERONLY",                        773);  /* -nosusp/-nosuspn options for brsvmod are only for user reservations */
define("LSBE_ADRSV_MOD_PRETIME_ACTIVE",                       774);  /* certain AR options cannot be modified when the reservation is active or in pre-time */
define("LSBE_ADRSV_MOD_NO_POSTSCRIPT",                        775);  /* post-time (-Ept) cannot be added/modified if AR does not have post-script (-Ep) */
define("LSBE_CSM_INVALID_SYNTAX",                             776);  /* Bad CSM requirement syntax */
define("LSBE_CSM_INVALID_JSM",                                777);  /* -jsm value is not valid in CSM requirement, must be y/n */
define("LSBE_CSM_INVALID_STEP_CGROUP",                        778);  /* -step_cgroup value is not valid in CSM requirement, must be y/n */
define("LSBE_CSM_INVALID_CORE_ISOLATION",                     779);  /* -core_isolation value is not valid in CSM requirement, must be y/n */
define("LSBE_CSM_INVALID_CN_MEM",                             780);  /* -cn_mem value for CSM requirement is out of range */
define("LSBE_CSM_INVALID_ALLOC_FLAGS",                        781);  /* -alloc_flags value is not valid in CSM requirement */
define("LSBE_CSM_INVALID_KEYWORD",                            782);  /* Bad keyword in CSM requirement */
define("LSBE_MOD_NON_CSM_JOB",                                783);  /* Cannot modify CSM options for a non-CSM job */
define("LSBE_NO_JOB_EXECHOME",                                784);  /* Job does not have execution home information */
define("LSBE_ESWITCH_ABORT",                                  785);  /* eswitch aborted request */
define("LSBE_RC_NOT_ENABLE",                                  786);  /* Resource Connector not enabled */
define("LSBE_MQTT_CONN",                                      787);  /* Cannot connect to Mosquitto */
define("LSBE_QUEUE_FWD_USER",                                 788);  /* User cannot use the queue */
define("LSBE_STAGE_FORMAT",                                   789);  /* Incorrect format for staging requirement */
define("LSBE_STAGE_CANT_BRUN",                                790);  /* Cannot force job with
                                                                      *  stage requirement to run.
                                                                     */
define("LSBE_STAGE_MIXED",                                    791);  /* Cannnot use other options with
                                                                      *  stage requirement
                                                                     */
define("LSBE_STAGE_ALLOCATION_PLANNER",                       792);  /* Need to set ALLOCATION_PLANNER parameter
                                                                      *  for job with stage requirement
                                                                     */
define("LSBE_STAGE_STORAGE",                                  793);  /* Need to set LSB_STAGE_STORAGE parameter
                                                                      *  for job with storage stage requirement
                                                                     */
define("LSBE_RESIZE_STAGING_TRANSFER_JOB",                    794);  /* Staging transfer jobs cannot be resized */
define("LSBE_RESIZE_STAGING_JOB",                             795);  /* Job with stage requirement annot be resized */
define("LSBE_STAGE_DATA_MIXED",                               796);  /* Job can't have both -data and -stage options */
define("LSBE_STAGE_AR_MIXED",                                 797);  /* Job can't have both -stage and -ar options */

define("LSBE_GPU_INVALID_GMODEL",                             798);  /*GPU gmodel specified is invalid */
define("LSBE_GPU_INVALID_GMEM",                               799);  /*GPU gmem specified is invalid */
define("LSBE_GPU_INVALID_GTILE",                              800);  /*GPU gtile specified is invalid */
define("LSBE_GPU_INVALID_NVLINK",                             801);  /*GPU nvlink specified is invalid */
define("LSBE_GPU_NUM_CONFLICT",                               802);  /*GPU num in -R and -gpu are conflict */
define("LSBE_GPU_RESOURCE_NOT_EXIST",                         803);  /*ngpus_physical not exist */
define("LSBE_BRUN_GPU_JOB",                                   804);  /* Cannot force a job with GPU requirement to run */
define("LSBE_BSUB_SECURE_PATH",                               805);  /* Server enforcing secure path */

define("LSBE_NO_MSG",                                         806);  /* "" */ /* Used if output to stderr is handled already */

define("LSBE_ADRSV_FORCE_CLOSED",                             807);  /* Cannot force delete a closed advance reservation */
define("LSBE_CU_BALANCE_BESTFIT",                             808);  /* balance and bestfit cannot both be used in a compute unit resource requirement */
define("LSBE_CU_BALANCE_BESTFIT_4SLOTS",                      809);  /* balance and bestfit cannot both be used in a compute unit resource requirement */
define("LSBE_MOD_NGPUS",                                      810);  /* Cannot modify job's ngpus_physical in rusage section of the resource requirement */
define("LSBE_HOST_IMAGE",                                     811);  /* Not find specific image in specific hosts*/
define("LSBE_IMAGE",                                          812);  /* The image name of the job is empty*/
define("LSBE_CSM_INVALID_SMT",                                813);  /* -smt value is not valid in CSM requirement */
define("LSBE_GPU_INVALID_AFFBIND",                            814);  /* GPU aff option is bad */

define("LSBE_LIC_SLA_EXIST",                                  815);  /* sla already exist */
define("LSBE_LIC_SLA_NAME_INVALID",                           816);  /* name invalid */
define("LSBE_LIC_SLA_ACCCTRL_INVALID",                        817);  /* accesscontrol invalid */
define("LSBE_LIC_SLA_AUTOATT_INVALID",                        818);  /* auto attach invalid */
define("LSBE_LIC_SLA_AUTOATT_EMPTY_ACCCTRL",                  819);  /* auto attach empty accCtrl */
define("LSBE_LIC_SLA_EMPTY_GPOOL_CONSUMER",                   820);  /* delete gpool all consumer*/
define("LSBE_LIC_SLA_ACCUSER_INVALID",                        821);  /* delete gpool all consumer*/
define("LSBE_LIC_SLA_ACCQUEUE_INVALID",                       822);  /* delete gpool all consumer*/
define("LSBE_LIC_SLA_ACCAPP_INVALID",                         823);  /* delete gpool all consumer*/
define("LSBE_LIC_SLA_ACCFG_INVALID",                          824);  /* delete gpool all consumer*/
define("LSBE_LIC_SLA_ACCPROJ_INVALID",                        825);  /* delete gpool all consumer*/
define("LSBE_LIC_SLA_ACCLICPROJ_INVALID",                     826);  /* delete gpool all consumer*/
define("LSBE_LIC_SLA_NOT_GUARANTEE",                          827);  /* not a guaranteed sla */
define("LSBE_LIC_SLA_CONF_INVALID",                           828);  /* general error */
define("LSBE_EXCLUDE_DEFAULT_UG",                             829);  /* Cannot exclude default user group for fairshare*/
define("LSBE_EXCLUDE_ENFORCE_UG",                             830);  /* Cannot exclude and enforce user groups for the same job*/
define("LSBE_DUP_NOTIFYAC",                                   831);  /* Cannot send the duplicated notification in short time*/
define("LSBE_LIC_SLA_UNSUP_AUTOATT",                          832);  /* only support non-autoattached gsla */
define("LSBE_RESIZE_GPU_MPS_SHARED_JOB",                      833);  /* Cannot resize GPU MPS Shared jobs. */
define("LSBE_MOD_NOTIFY_ARRAY",                               834);  /* modification is rejected because not support -notify or -notifyn for any specific job array.*/
define("LSBE_USER_IMPERSONATE",                               835);  /* Not authorized to submit with the -user option */
define("LSBE_KUBE_JOB_UNSUPPORTED",                           836);  /* Cannot perform the operation on Kubernetes jobs */
define("LSBE_KUBE_MOD_RUNJOB",                                837);  /* Cannot modify a started Kubernetes job */
define("LSBE_KUBE_SWITCH_RUNJOB",                             838);  /* Cannot switch a started Kubernetes job to another queue */
define("LSBE_KUBE_STOP_RUNJOB",                               839);  /* Cannot stop a started Kubernetes job */
define("LSBE_KUBE_PODJOB_UNSUPPORTED",                        840);  /* Cannot perform the operation on Kubernetes control jobs */
define("LSBE_KUBE_JOB_OPTION",                                841);  /* Not support a Kubernetes job with the submission options */
define("LSBE_KUBE_RSRCREQ",                                   842);  /* Kubernetes job cannot work with compound or alternative resreq or "||" used in rusage[] */
define("LSBE_KUBE_CONNECTOR",                                 843);  /* Accept Kubernetes job only for LSF_Connector_for_Kubernetes entitlement*/
define("LSBE_RUSAGE_TYPE_RESRSV_LIMIT",                       844);  /* Job cannot have a different rusage configuation from the default when RESRSV_LIMIT is configured */
define("LSBE_LOCK_ID_NOT_VALID",                              845);  /* Lock ID is not valid */
define("LSBE_LOCK_ID_EXIST",                                  846);  /* Lock ID is already attached to the host */
define("LSBE_LOCK_ID_NOT_FOUND",                              847);  /* Lock ID is not found */
define("LSBE_LOCK_ID_CLOSED",                                 848);  /* Cannot open a closed host until all lock IDs are removed */
/*  error code about attribute affinity */
define("LSBE_ATTR_REQ",                                       849);  /* The job attribute requirement is not valid */
define("LSBE_ATTR_NAME",                                      850);  /* The attribute name is not valid */
define("LSBE_ATTR_MAX_NUM",                                   851);  /* The cluster-wide attribute number limit is reached */
define("LSBE_ATTR_INVALID_SERVER",                            852);  /* The specified host is not a valid server in the cluster to create attributes */
define("LSBE_ATTR_CREATED_BY_OTHER",                          853);  /* Cannot create or delete an attribute that was created by another user */
define("LSBE_ATTR_NO_ATTR",                                   854);  /* No attributes in the cluster to show or delete */
define("LSBE_ATTR_NO_SPECIFIED_ATTR",                         855);  /* The cluster does not have any specified attributes to show or delete */
define("LSBE_ATTR_NO_ATTR_ON_HOST",                           856);  /* The current user did not create the specified attribute on the specified host to delete*/
define("LSBE_NO_SAMEAFF_REMOTE_ONLY",                         857);  /* The job with the samehost or samecu request cannot submit to just remote queues */
define("LSBE_NO_JOBAFF_LEASE",                                858);  /* The job with the jobaff request cannot submit to lease queues */
define("LSBE_ATTR_TOO_MANY_ATTRS",                            859);  /* Too many attributes specified at once.
                                                                      *  The number of attributes exceeds the cluster-wide maximum attribute limit
                                                                     */
define("LSBE_ATTR_AFFINITY_DISABLED",                         860);  /* Job cannot have attribute affinity requirement. Attribute affinity is disabled with ATTR_CREATE_USERS = none */
define("LSBE_SAME_JOB_AFFINITY_DISABLED",                     861);  /* Job cannot have samehost/samecu affinity requirement.
                                                                      *  samehost/samecu affinity is disabled with SAME_JOB_AFFINITY = N
                                                                     */
define("LSBE_GPU_INVALID_BLOCK",                              862);  /* GPU block option is bad */
define("LSBE_GPU_INVALID_GPACK",                              863);  /* GPU pack option is bad */
define("LSBE_IMAGE_AFFINITY_DISABLED",                        864);  /* Docker image affinity feature disabled by setting LSF_DOCKER_IMAGE_UPDATE_INTERVAL = 0 or not defined in lsf.conf */
define("LSBE_LOAD_EXT_LIBRARY",                               865);  /* Failed to load external library */
define("LSBE_GPU_INVALID_GLINK",                              866);  /* GPU link option is bad */
define("LSBE_GPU_INVALID_GVENDOR",                            867);  /* GPU vendor option is bad */
define("LSBE_GPU_VENDOR_CONFLICT",                            868);  /* More than one type GPU feature are specified */
define("LSBE_GPU_GLINK_NVLINK",                               869);  /* Cann't specify nvlink and glink at same time */
define("LSBE_GPU_INVALID_MIG_SPEC",                           870);  /* Invalid mig in gpu requirement */
define("LSBE_GPU_MIG_MPS_SHARED_UNSUPPORT",                   871);  /* mps = shared is not supported for mig job */
define("LSBE_BWAIT_IN_JOB_DISABLED",                          872);  /* Cannot use bwait within a job when LSB_BWAIT_IN_JOBS is set to N in lsf.conf */
define("LSBE_NUM_ERR",                                        873);  /* Number of the above error codes */

define('GUAR_RESOURCE_POOL_POLICIES_NONE',                 0);
define('GUAR_RESOURCE_POOL_POLICIES_LOAN_DELAY',           1);
define('GUAR_RESOURCE_POOL_POLICIES_LOAN_RESTRICT',        2);
define('GUAR_RESOURCE_POOL_POLICIES_RETAIN_PERCENT',       4);
define('GUAR_RESOURCE_POOL_POLICIES_LOAN_ALLOCATED_HOSTS', 8);

define ('GUAR_CONSUMER_SHARE_TYPE_ABSOLUTE',      1);
define ('GUAR_CONSUMER_SHARE_TYPE_PERCENT',       2);

define('POPUP_WINDOW_ONCLICK_CMD', "var w=window.open(this.href,'help','left=30px,top=30px,width=1100px,height=700px,resizable=yes,scrollbars=yes,toolbar=yes,menubar=yes,location=yes').focus();return false;");
