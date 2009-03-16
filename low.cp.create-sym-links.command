#!/bin/bash

# This script creates symlinks from the local GIT repo into your EE install.

dirname=`dirname "$0"`

echo ""
echo "You are about to install Low CP"
echo "-------------------------------"
echo ""
echo "Enter the path to your ExpressionEngine Install without a trailing slash [ENTER]:"
read ee_path
echo "Enter your system folder name [ENTER]:"
read ee_system_folder

ln -s -f "$dirname"/system/extensions/ext.low_cp.php "$ee_path"/"$ee_system_folder"/extensions/ext.low_cp.php