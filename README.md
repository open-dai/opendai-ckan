opendai-ckan
============

exposure of WSO2API manager as CKAN data

This script exposes the APIs published in a WSO2 API Manager with the CKAN protocol
The project make use of the WSO2 API Manager APIs to get the information and transforming it into the CKAN format (compliant with version 1.0 and 2.0).
The format used exposes just a subsystem of the attributes due to the limited number of information present in the API manager.
The service exposes two API:
- /package to get the Package List
- /package/:id to get the Package Metadata

These API will allow to federate and catalogue APIs present in the API Manager from a CKAN portal of a federation system like the HOMER one.
