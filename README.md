# Kakadu Converter
This is a simple wrapper around the Kakadu JPEG2000 tools. It is meant to allow the conversion of a source
file into a JPEG2000 file with a specific set of parameters.

## Requirements
A licensed copy of the Kakadu tools. This is not included in this repository.

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

Clevels=7 Cprecincts="{256,256},{256,256},{256,256},{256,256},{256,256},{256,256},{256,256},{128,128}" Corder=RPCL Cblk="{64,64}" Cuse_sop=yes ORGgen_plt=yes ORGtparts=R ORGgen_tlm=8 Cmodes=HT Cplex="{6,EST,0.25,-1}"

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