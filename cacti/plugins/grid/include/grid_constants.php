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
global $rtm, $signal;
global $config;

$path_rtm_top=grid_get_path_rtm_top();
define('RTM_ROOT', $path_rtm_top. '/rtm');
define('DELIM', '/');
define('EXT', '');

$grid_version_numbers = array('1.04', '1.5', '1.5.2', '2.0', '2.0.1', '2.1', '2.1.1', '2.1.2',
							 '8.0', '8.0.1', '8.0.2', '8.0.2.2', '8.3', '9.1', '9.1.2', '9.1.3', '9.1.4',
							 '10.1', '10.1.0.1', '10.1.0.2', '10.1.0.3', '10.1.0.4', '10.1.0.5', '10.1.0.6',
							 '10.2', '10.2.0.1');

$rtm = array(
	'lsf91' => array(
		'PATH' => RTM_ROOT . DELIM . 'lsf91' . DELIM,
		'VERSION' => '9.1',
		'LSF_ENVDIR' => RTM_ROOT . DELIM . 'etc' . DELIM,
		'LSF_SERVERDIR' => RTM_ROOT . DELIM . 'lsf91' . DELIM . 'bin' . DELIM,
		'DESC' => 'Poller for LSF 9.1'
	),
	'lsf1010' => array(
		'PATH' => RTM_ROOT . DELIM . 'lsf101' . DELIM,
		'VERSION' => '10.1',
		'LSF_ENVDIR' => RTM_ROOT . DELIM . 'etc' . DELIM,
		'LSF_SERVERDIR' => RTM_ROOT . DELIM . 'lsf101' . DELIM . 'bin' . DELIM,
		'DESC' => 'Poller for LSF 10.1'
	),
	'lsf1017' => array(
		'PATH' => RTM_ROOT . DELIM . 'lsf1017' . DELIM,
		'VERSION' => '10.1',
		'LSF_ENVDIR' => RTM_ROOT . DELIM . 'etc' . DELIM,
		'LSF_SERVERDIR' => RTM_ROOT . DELIM . 'lsf1017' . DELIM . 'bin' . DELIM,
		'DESC' => 'Poller for LSF 10.1.0.7'
	)
);

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

//LSF_SERVERDIR

if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN'){
	define('LSF_SERVERDIR91', RTM_ROOT . DELIM . 'lsf91' . DELIM . 'bin');
	define('LSF_SERVERDIR1010', RTM_ROOT . DELIM . 'lsf101' . DELIM . 'bin');
	define('LSF_SERVERDIR1017', RTM_ROOT . DELIM . 'lsf1017' . DELIM . 'bin');
}else{
	define('LSF_SERVERDIR91', $path_rtm_top. '/rtm/lsf91/etc');
	define('LSF_SERVERDIR1010', $path_rtm_top. '/rtm/lsf101/etc');
	define('LSF_SERVERDIR1017', $path_rtm_top. '/rtm/lsf1017/etc');
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
define('J_EXCEPT_OVERRUN',		      0x02);
define('J_EXCEPT_UNDERUN',              0x04);
define('J_EXCEPT_IDLE',                 0x80);
define('J_EXCEPT_RUNTIME_EST_EXCEEDED', 0x100);

/* exception showed by bjobs -l and bacct -l*/
define('OVERRUN',                           'overrun');
define('UNDERRUN',                         'underrun');
define('IDLE',                                 'idle');
define('SPACE',                                  '  ');
define('RUNTIME_EST_EXCEEDED', 'runtime_est_exceeded');

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
define('TERM_MC_RECALL',         30);
define('TERM_RC_RECLAIM',        31);
define('TERM_CSM_ALLOC',         32);
define('TERM_KUBE',              33);

define('MSG_TERM_UNKNOWN',                                                                   'job exited, reason unknown');
define('MSG_TERM_PREEMPT',                                                                  'job killed after preemption');
define('MSG_TERM_WINDOW',                                                   'job killed after queue run window is closed');
define('MSG_TERM_LOAD',                                                         'job killed after load exceeds threshold');
define('MSG_TERM_OTHER',                                                                    'job killed after preemption');
define('MSG_TERM_RUNLIMIT',                                                'job killed after reaching LSF run time limit');
define('MSG_TERM_DEADLINE',                                                           'job killed after deadline expires');
define('MSG_TERM_PROCESSLIMIT',                                             'job killed after reaching LSF process limit');
define('MSG_TERM_FORCE_OWNER',                                             'job killed by owner without time for cleanup');
define('MSG_TERM_FORCE_ADMIN',                         'job killed by root or LSF administrator without time for cleanup');
define('MSG_TERM_REQUEUE_OWNER',                                                       'job killed and requeued by owner');
define('MSG_TERM_REQUEUE_ADMIN',                                   'job killed and requeued by root or LSF administrator');
define('MSG_TERM_CPULIMIT',                                               'job killed after reaching LSF CPU usage limit');
define('MSG_TERM_CHKPNT',                                                                'job killed after checkpointing');
define('MSG_TERM_OWNER',                                                                            'job killed by owner');
define('MSG_TERM_ADMIN',                                                         'job killed by root or an administrator');
define('MSG_TERM_MEMLIMIT',                                            'job killed after reaching LSF memory usage limit');
define('MSG_TERM_EXTERNAL_SIGNAL',                                               'job killed by a signal external to LSF');
define('MSG_TERM_RMS',                                                                 'job terminated abnormally in RMS');
define('MSG_TERM_ZOMBIE',                                                          'job killed when LSF is not available');
define('MSG_TERM_SWAP',                                                  'job killed after reaching LSF swap usage limit');
define('MSG_TERM_THREADLIMIT',                                               'job killed after reaching LSF thread limit');
define('MSG_TERM_SLURM',                                                             'job terminated abnormally in SLURM');
define('MSG_TERM_BUCKET_KILL',                                                                  'job killed with bkill-b');
define('MSG_TERM_CTRL_PID',                                                       'job terminated after control PID died');
define('MSG_TERM_CWD_NOTEXIST',     'Current working directory is not accessible or does not exist on the execution host');
define('MSG_TERM_MC_RECALL',                                           'job killed by LSF due to MultiCluster job recall');
define('MSG_TERM_RC_RECLAIM', 'job killed and requeued when an LSF resource connector execution host is reclaimed by EGO');
define('MSG_TERM_CSM_ALLOC',                                          'job killed by LSF due to CSM allocation API error');
define('MSG_TERM_KUBE',                                                 'job killed by LSF due to Kubernetes integration');

/* base error messages */
define('LSE_NO_ERR',               0);          /* initial value */
define('LSE_BAD_XDR',              1);          /* Error during XDR */
define('LSE_MSG_SYS',              2);          /* Failed in sending/receiving a msg */
define('LSE_BAD_ARGS',             3);          /* supplied arguments invalid */
define('LSE_MASTR_UNKNW',          4);          /* cannot find out the master LIM*/
define('LSE_LIM_DOWN',             5);          /* LIM does not respond */
define('LSE_PROTOC_LIM',           6);          /* LIM protocol error */
define('LSE_SOCK_SYS',             7);          /* A socket operation has failed */
define('LSE_ACCEPT_SYS',           8);          /* Failed in a accept system call */
define('LSE_BAD_TASKF',            9);          /* Bad LSF task file format*/
define('LSE_NO_HOST',              10);         /* No enough ok hosts found by LIM*/
define('LSE_NO_ELHOST',            11);         /* No host is found eligible by LIM */
define('LSE_TIME_OUT',             12);         /* communication timed out */
define('LSE_NIOS_DOWN',            13);         /* nios has not been started. */
define('LSE_LIM_DENIED',           14);         /* Operation permission denied by LIM */
define('LSE_LIM_IGNORE',           15);         /* Operation ignored by LIM */
define('LSE_LIM_BADHOST',          16);         /* host name not recognizable by LIM*/
define('LSE_LIM_ALOCKED',          17);         /* LIM already locked */
define('LSE_LIM_NLOCKED',          18);         /* LIM was not locked. */
define('LSE_LIM_BADMOD',           19);         /* unknown host model. */
define('LSE_SIG_SYS',              20);         /* A signal related system call failed*/
define('LSE_BAD_EXP',              21);         /* bad resource req. expression*/
define('LSE_NORCHILD',             22);         /* no remote child */
define('LSE_MALLOC',               23);         /* memory allocation failed */
define('LSE_LSFCONF',              24);         /* unable to open lsf.conf */
define('LSE_BAD_ENV',              25);         /* bad configuration environment */
define('LSE_LIM_NREG',             26);         /* Lim is not a registered service*/
define('LSE_RES_NREG',             27);         /* Res is not a registered service*/
define('LSE_RES_NOMORECONN',       28);         /* RES is serving too many connections*/
define('LSE_BADUSER',              29);         /* Bad user ID for REX */
define('LSE_RES_ROOTSECURE',       30);         /* Root user rejected          */
define('LSE_RES_DENIED',           31);         /* User permission denied      */
define('LSE_BAD_OPCODE',           32);         /* bad op code */
define('LSE_PROTOC_RES',           33);         /* RES Protocol error */
define('LSE_RES_CALLBACK',         34);         /* RES callback fails          */
define('LSE_RES_NOMEM',            35);         /* RES malloc fails            */
define('LSE_RES_FATAL',            36);         /* RES system call error       */
define('LSE_RES_PTY',              37);         /* RES cannot alloc pty        */
define('LSE_RES_SOCK',             38);         /* RES socketpair fails        */
define('LSE_RES_FORK',             39);         /* RES fork fails              */
define('LSE_NOMORE_SOCK',          40);         /* Privileged socks run out    */
define('LSE_WDIR',                 41);         /* getwd() failed */
define('LSE_LOSTCON',              42);         /* Connection has been lost    */
define('LSE_RES_INVCHILD',         43);         /* No such remote child        */
define('LSE_RES_KILL',             44);         /* Remote kill permission denied */
define('LSE_PTYMODE',              45);         /* ptymode inconsistency       */
define('LSE_BAD_HOST',             46);         /* Bad hostname                */
define('LSE_PROTOC_NIOS',          47);         /* NIOS protocol error     */
define('LSE_WAIT_SYS',             48);         /* A wait system call failed */
define('LSE_SETPARAM',             49);         /* Bad parameters for setstdin */
define('LSE_RPIDLISTLEN',          50);         /* Insufficient list len for rpids */
define('LSE_BAD_CLUSTER',          51);         /* Invalid cluster name */
define('LSE_RES_VERSION',          52);         /* Incompatible versions of tty params */
define('LSE_EXECV_SYS',            53);         /* Failed in a execv() sys call*/
define('LSE_RES_DIR',              54);         /* No such directory */
define('LSE_RES_DIRW',             55);         /* The directory may not be accessible*/
define('LSE_BAD_SERVID',           56);         /* the service ID is invalid */
define('LSE_NLSF_HOST',            57);         /* request from a non lsf host */
define('LSE_UNKWN_RESNAME',        58);         /* unknown resource name specified */
define('LSE_UNKWN_RESVALUE',       59);         /* unknown resource value */
define('LSE_TASKEXIST',            60);         /* the task already registered */
define('LSE_BAD_TID',              61);         /* the task does not exist */
define('LSE_TOOMANYTASK',          62);         /* the task table is full */
define('LSE_LIMIT_SYS',            63);         /* A resource limit sys call failed*/
define('LSE_BAD_NAMELIST',         64);         /* bad index name list */
define('LSE_NO_LICENSE',           65);         /* no software license for host */
define('LSE_LIM_NOMEM',            66);         /* lim malloc failure */
define('LSE_NIO_INIT',             67);         /* nio not initialized. */
define('LSE_CONF_SYNTAX',          68);         /* Bad lsf.conf/lsf.sudoers syntax */
define('LSE_FILE_SYS',             69);         /* A file operation failed */
define('LSE_CONN_SYS',             70);         /* A connect sys call failed */
define('LSE_SELECT_SYS',           71);         /* A select system call failed */
define('LSE_EOF',                  72);         /* Reached the end of file */
define('LSE_ACCT_FORMAT',          73);         /* Bad lsf.acct file format */
define('LSE_BAD_TIME',             74);         /* Bad time specification */
define('LSE_FORK',                 75);         /* Unable to fork child */
define('LSE_PIPE',                 76);         /* Failed to setup pipe */
define('LSE_ESUB',                 77);         /* esub/eexec file not found */
define('LSE_DCE_EXEC',             78);         /* dce task exec fail */
define('LSE_EAUTH',                79);         /* external authentication failed */
define('LSE_NO_FILE',              80);         /* cannot open file */
define('LSE_NO_CHAN',              81);         /* out of communication channels */
define('LSE_BAD_CHAN',             82);         /* bad communication channel */
define('LSE_INTERNAL',             83);         /* internal library error */
define('LSE_PROTOCOL',             84);         /* protocol error with server */
define('LSE_THRD_SYS',             85);         /* A thread system call failed (NT only)*/
define('LSE_MISC_SYS',             86);         /* A system call failed */
define('LSE_LOGON_FAIL',           87);         /* Failed to logon user (NT only) */
define('LSE_RES_RUSAGE',           88);         /* Failed to get rusage from RES */
define('LSE_NO_RESOURCE',          89);         /* no shared resource defined */
define('LSE_BAD_RESOURCE',         90);         /* Bad resource name */
define('LSE_RES_PARENT',           91);         /* res child Failed to contact parent */
define('LSE_NO_PASSWD',            92);         /* no password for user */
define('LSE_SUDOERS_CONF',         93);         /* lsf.sudoers file error */
define('LSE_SUDOERS_ROOT',         94);         /* lsf.sudoers not owned by root */
define('LSE_I18N_SETLC',           95);         /* i18n setlocale failed */
define('LSE_I18N_CATOPEN',         96);         /* i18n catopen failed */
define('LSE_I18N_NOMEM',           97);         /* i18n malloc failed */
define('LSE_NO_MEM',               98);         /* Cannot alloc memory */
define('LSE_REGISTRY_SYS',         99);         /* A registry system call failed (NT) */
define('LSE_FILE_CLOSE',          100);         /* Close a NULL-FILE pointer */
define('LSE_LIMCONF_NOTREADY',    101);         /* LIM configuration is not ready yet */
define('LSE_MASTER_LIM_DOWN',     102);         /* for LIM_CONF master LIM down */
define('LSE_MLS_INVALID',         103);         /* invalid MLS label */
define('LSE_MLS_CLEARANCE',       104);         /* not enough clearance */
define('LSE_MLS_RHOST',           105);         /* reject by rhost.conf */
define('LSE_MLS_DOMINATE',        106);         /* require label not dominate */
define('LSE_NO_CAL',              107);         /* Win32: No more connections can be */
define('LSE_NO_NETWORK',          108);         /* Network location can not be found */
define('LSE_GETCONF_FAILED',      109);         /* Failed to get configuration */
define('LSE_TSSINIT',             110);         /* Win32: terminal service not properly initialized */
define('LSE_DYNM_DENIED',         111);         /* Dynamic addHost denied */
define('LSE_LIC_OVERUSE',         112);         /* In license overuse status */
define('LSE_NERR',                113);         /* Moving number, size of ls_errmsg[] */

/* important batch error messages */
define('LSBE_NO_ERROR',             0); /* No error at all */
define('LSBE_NO_JOB',               1); /* No matching job found */
define('LSBE_NOT_STARTED',          2); /* Job not started yet */
define('LSBE_JOB_STARTED',          3); /* Job already started */
define('LSBE_JOB_FINISH',           4); /* Job already finished */
define('LSBE_STOP_JOB',             5); /* Ask sbatchd to stop the wrong job */
define('LSBE_DEPEND_SYNTAX',        6); /* Depend_cond syntax error */
define('LSBE_EXCLUSIVE',            7); /* Queue doesn't accept EXCLUSIVE job */
define('LSBE_ROOT',                 8); /* Root is not allowed to submit jobs */
define('LSBE_MIGRATION',            9); /* Job is already being migrated */
define('LSBE_J_UNCHKPNTABLE',      10); /* Job is not chkpntable */
define('LSBE_NO_OUTPUT',           11); /* Job has no output so far */
define('LSBE_NO_JOBID',            12); /* No jobId can be used now */
define('LSBE_ONLY_INTERACTIVE',    13); /* Queue only accepts bsub -I job */
define('LSBE_NO_INTERACTIVE',      14); /* Queue doesn't accept bsub -I job */
define('LSBE_NO_USER',             15); /* No user defined in lsb.users file */
define('LSBE_BAD_USER',            16); /* Bad user name */
define('LSBE_PERMISSION',          17); /* User permission denied */
define('LSBE_BAD_QUEUE',           18); /* No such queue in the system */
define('LSBE_QUEUE_NAME',          19); /* Queue name should be given */
define('LSBE_QUEUE_CLOSED',        20); /* Queue has been closed */
define('LSBE_QUEUE_WINDOW',        21); /* Queue windows are closed */
define('LSBE_QUEUE_USE',           22); /* User cannot use the queue */
define('LSBE_BAD_HOST',            23); /* Bad host name or host group name' */
define('LSBE_PROC_NUM',            24); /* Too many processors requested */
define('LSBE_NO_HPART',            25); /* No host partition in the system */
define('LSBE_BAD_HPART',           26); /* Bad host partition name */
define('LSBE_NO_GROUP',            27); /* No group defined in the system */
define('LSBE_BAD_GROUP',           28); /* Bad host/user group name */
define('LSBE_QUEUE_HOST',          29); /* Host is not used by the queue */
define('LSBE_UJOB_LIMIT',          30); /* User reach UJOB_LIMIT of the queue */
define('LSBE_NO_HOST',             31); /* No host available for migration */
define('LSBE_BAD_CHKLOG',          32); /* chklog is corrupted */
define('LSBE_PJOB_LIMIT',          33); /* User reach PJOB_LIMIT of the queue */
define('LSBE_NOLSF_HOST',          34); /* request from non LSF host rejected*/
define('LSBE_BAD_ARG',             35); /* Bad argument for lsblib call */
define('LSBE_BAD_TIME',            36); /* Bad time spec for lsblib call */
define('LSBE_START_TIME',          37); /* Start time is later than end time */
define('LSBE_BAD_LIMIT',           38); /* Bad CPU limit specification */
define('LSBE_OVER_LIMIT',          39); /* Over hard limit of queue */
define('LSBE_BAD_CMD',             40); /* Empty job (command) */
define('LSBE_BAD_SIGNAL',          41); /* Bad signal value; not supported */
define('LSBE_BAD_JOB',             42); /* Bad job name */
define('LSBE_QJOB_LIMIT',          43); /* Queue reach QJOB_LIMIT of the queue */
define('LSBE_UNKNOWN_EVENT',       45);    /* Unknown event in event log file */
define('LSBE_EVENT_FORMAT',        46);    /* bad event format in event log file */
define('LSBE_EOF',                 47);    /* End of file */
define('LSBE_MBATCHD',             50);    /* Mbatchd internal error */
define('LSBE_SBATCHD',             51);    /* Sbatchd internal error */
define('LSBE_LSBLIB',              52);    /* lsbatch lib internal error */
define('LSBE_LSLIB',               53);    /* LSLIB call fails */
define('LSBE_SYS_CALL',            54);    /* System call fails */
define('LSBE_NO_MEM',              55);    /* Cannot alloc memory */
define('LSBE_SERVICE',             56);    /* Lsbatch service not registered */
define('LSBE_NO_ENV',              57);    /* LSB_SHAREDIR not defined */
define('LSBE_CHKPNT_CALL',         58);    /* chkpnt system call fail */
define('LSBE_NO_FORK',             59);    /* mbatchd cannot fork */
define('LSBE_PROTOCOL',            60);    /* LSBATCH protocol error */
define('LSBE_XDR',                 61);    /* XDR en/decode error */
define('LSBE_PORT',                62);    /* No appropriate port can be bound */
define('LSBE_TIME_OUT',            63);    /* Timeout in contacting mbatchd */
define('LSBE_CONN_TIMEOUT',        64);    /* Timeout on connect() call */
define('LSBE_CONN_REFUSED',        65);    /* Connection refused by server */
define('LSBE_CONN_EXIST',          66);    /* server connection already exists */
define('LSBE_CONN_NONEXIST',       67);    /* server is not connected */
define('LSBE_SBD_UNREACH',         68);    /* sbd cannot be reached */
define('LSBE_OP_RETRY',            69);    /* Operation cannot be performed right now, op. will be retried. */
define('LSBE_USER_JLIMIT',         70);    /* user has no enough job slots */

define('GUAR_RESOURCE_POOL_POLICIES_NONE',               0);
define('GUAR_RESOURCE_POOL_POLICIES_LOAN_DELAY',         1);
define('GUAR_RESOURCE_POOL_POLICIES_LOAN_RESTRICT',      2);
define('GUAR_RESOURCE_POOL_POLICIES_RETAIN_PERCENT',     4);

define ('GUAR_CONSUMER_SHARE_TYPE_ABSOLUTE',      1);
define ('GUAR_CONSUMER_SHARE_TYPE_PERCENT',       2);

define('POPUP_WINDOW_ONCLICK_CMD', "var w=window.open(this.href,'help','left=30px,top=30px,width=1100px,height=700px,resizable=yes,scrollbars=yes,toolbar=yes,menubar=yes,location=yes').focus();return false;");
