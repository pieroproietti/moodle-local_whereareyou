#!/bin/bash
cd $MOODLE/local
git clone https://github.com/pieroproietti/moodle-local_whereareyou whereareyou 
cd $HOME
ln -s $MOODLE/local/whereareyou moodle-local_whereareyou 
echo moodle-local_whereareyou  installato in $MOODLE/local/whereareyou
