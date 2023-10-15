# EasyVereinAuth
Authentication extension for easyVerein

## Installation
This extension requires PluggableAuth.

```
wfLoadExtension( 'PluggableAuth' );
wfLoadExtension( 'EasyVereinAuth' );
$wgEasyVereinAuth_AssociationCode = "FREILab";
$wgGroupPermissions['*']['autocreateaccount'] = true;
```
