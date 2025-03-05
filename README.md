# Kakadu Converter
This is a simple wrapper around the Kakadu JPEG2000 tools. It is meant to allow the conversion of a source
file into a JPEG2000 file with a specific set of parameters.

## Requirements
1. A licensed copy of the Kakadu tools. This is not included in this repository.
1. Imagemagick for identifying the source file and uncompressing (if necessary).

## Configuration
There are 5 environment variables that can be set to configure the Kakadu tools:

| Variable                     | Description                                        | Default               |
|------------------------------|----------------------------------------------------|-----------------------|
| KAKADU_COMPRESS_PATH         | Path to Kakadu kdu_compress command                | /path/to/kdu_compress |
| KAKADU_IMAGEMAGICK_IDENTIFY_PATH | Path to Imagemagick Identify command               | /path/to/identify     |
| KAKADU_IMAGEMAGICK_CONVERT_PATH | Path to Imagemagick Convert command                | /path/to/convert      |
| KAKADU_TEMP_DIR              | Temporary directory to use when processing JP2s    | /var/tmp              |
| KAKADU_DEFAULT_FORMAT        | The default format to generate                     | jpx                   |

You should create a file called `.env.local` in the root of the project and change any of these variables there.

### Derivative clean up

The Kakadu converter will create temporary files when processing images. These files need to remain to be read while
streaming the response. 

The files will have a prefix of `kakadu_derivative_` and will be in the `KAKADU_TEMP_DIR`.

Once the derivative is streamed out, the files can be deleted. 

An example cron job that deletes files not accessed in the last 5 minutes is:
```bash
# Clean kakadu_derivative_* files from temp
SHELL=/bin/bash
PATH=/sbin:/bin:/usr/sbin:/usr/bin
MAILTO=you@yourplace.ca
*/5 * * * * root find /var/tmp -type f -iname 'kakadu_derivative_*' -amin +5 -delete
```

## Defaults
For lossy HTJ2K compression, the default parameters are:
```
 Clayers=1
 Clevels=7
 Cprecincts={256,256},{256,256},{256,256},{256,256},{256,256},{256,256},{256,256},{128,128}
 Corder=RPCL
 Cblk={64,64}
 Cuse_sop=yes
 ORGgen_plt=yes
 ORGtparts=R
 ORGgen_tlm=8
 Cmodes=HT
 Cplex={6,EST,0.25,-1}
```

For lossless HTJ2K compression, the default parameters are:
```
 Clayers=1
 Clevels=7
 Cprecincts={256,256},{256,256},{256,256},{256,256},{256,256},{256,256},{256,256},{128,128}
 Corder=RPCL
 Cblk={64,64}
 Cuse_sop=yes
 ORGgen_plt=yes
 ORGtparts=R
 ORGgen_tlm=8
 Cmodes=HT
 Creversible=yes
```

For lossy JP2 compression, the default parameters are:
```
 Clayers=1 
 Clevels=7 
 Cprecincts={256,256},{256,256},{256,256},{256,256},{256,256},{256,256},{256,256},{128,128} 
 Corder=RPCL 
 Cblk={64,64} 
 Cuse_sop=yes 
 ORGgen_plt=yes 
 ORGtparts=R 
 ORGgen_tlm=8
```

For lossless JP2 compression, the default parameters are:
```
 Clayers=1
 Clevels=7
 Cprecincts={256,256},{256,256},{256,256},{256,256},{256,256},{256,256},{256,256},{128,128}
 Corder=RPCL
 Cblk={64,64}
 Cuse_sop=yes
 ORGgen_plt=yes
 ORGtparts=R
 ORGgen_tlm=8
 Creversible=yes
```