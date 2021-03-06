<?php

class block_social_activities extends block_list {
    function init(){
        $this->title = get_string('pluginname', 'block_social_activities');
    }

    function applicable_formats() {
        return array('course-view-social' => true);
    }

    function get_content() {
        global $USER, $CFG, $DB, $OUTPUT;

        if ($this->content !== NULL) {
            return $this->content;
        }

        $this->content = new stdClass();
        $this->content->items = array();
        $this->content->icons = array();
        $this->content->footer = '';

        if (empty($this->instance)) {
            return $this->content;
        }

        $course = $this->page->course;

        require_once($CFG->dirroot.'/course/lib.php');

        $context = get_context_instance(CONTEXT_COURSE, $course->id);
        $isediting = $this->page->user_is_editing() && has_capability('moodle/course:manageactivities', $context);
        $modinfo = get_fast_modinfo($course);

/// extra fast view mode
        if (!$isediting) {
            if (!empty($modinfo->sections[0])) {
                $options = array('overflowdiv'=>true);
                foreach($modinfo->sections[0] as $cmid) {
                    $cm = $modinfo->cms[$cmid];
                    if (!$cm->uservisible) {
                        continue;
                    }
                    if ($cm->modname == 'label') {
                        $this->content->items[] = format_text($cm->extra, FORMAT_HTML, $options);
                        $this->content->icons[] = '';
                    } else {
                        $linkcss = $cm->visible ? '' : ' class="dimmed" ';
                        $instancename = format_string($cm->name, true, $course->id);
                        //Accessibility: incidental image - should be empty Alt text
                        if (!empty($cm->icon)) {
                            $icon = $OUTPUT->pix_url($cm->icon);
                        } else {
                            $icon = $OUTPUT->pix_url('icon', $cm->modname);
                        }
                        $icon = '<img src="'.$icon.'" class="icon" alt="" />&nbsp;';
                        $this->content->items[] = '<a title="'.$cm->modplural.'" '.$linkcss.' '.$cm->extra.
                            ' href="'.$CFG->wwwroot.'/mod/'.$cm->modname.'/view.php?id='.$cm->id.'">'.$icon.$instancename.'</a>';
                    }
                }
            }
            return $this->content;
        }


/// slow & hacky editing mode
        $ismoving = ismoving($course->id);
        $sections = get_all_sections($course->id);

        if(!empty($sections) && isset($sections[0])) {
            $section = $sections[0];
        }

        if (!empty($section)) {
            get_all_mods($course->id, $mods, $modnames, $modnamesplural, $modnamesused);
        }

        $groupbuttons = $course->groupmode;
        $groupbuttonslink = (!$course->groupmodeforce);

        if ($ismoving) {
            $strmovehere = get_string('movehere');
            $strmovefull = strip_tags(get_string('movefull', '', "'$USER->activitycopyname'"));
            $strcancel= get_string('cancel');
            $stractivityclipboard = $USER->activitycopyname;
        }
    /// Casting $course->modinfo to string prevents one notice when the field is null
        $editbuttons = '';

        if ($ismoving) {
            $this->content->icons[] = '&nbsp;<img align="bottom" src="'.$OUTPUT->pix_url('t/move') . '" class="iconsmall" alt="" />';
            $this->content->items[] = $USER->activitycopyname.'&nbsp;(<a href="'.$CFG->wwwroot.'/course/mod.php?cancelcopy=true&amp;sesskey='.sesskey().'">'.$strcancel.'</a>)';
        }

        if (!empty($section) && !empty($section->sequence)) {
            $sectionmods = explode(',', $section->sequence);
            $options = array('overflowdiv'=>true);
            foreach ($sectionmods as $modnumber) {
                if (empty($mods[$modnumber])) {
                    continue;
                }
                $mod = $mods[$modnumber];
                if (!$ismoving) {
                    if ($groupbuttons) {
                        if (! $mod->groupmodelink = $groupbuttonslink) {
                            $mod->groupmode = $course->groupmode;
                        }

                    } else {
                        $mod->groupmode = false;
                    }
                    $editbuttons = '<br />'.make_editing_buttons($mod, true, true);
                } else {
                    $editbuttons = '';
                }
                if ($mod->visible || has_capability('moodle/course:viewhiddenactivities', $context)) {
                    if ($ismoving) {
                        if ($mod->id == $USER->activitycopy) {
                            continue;
                        }
                        $this->content->items[] = '<a title="'.$strmovefull.'" href="'.$CFG->wwwroot.'/course/mod.php?moveto='.$mod->id.'&amp;sesskey='.sesskey().'">'.
                            '<img style="height:16px; width:80px; border:0px" src="'.$OUTPUT->pix_url('movehere') . '" alt="'.$strmovehere.'" /></a>';
                        $this->content->icons[] = '';
                    }
                    $instancename = $modinfo->cms[$modnumber]->name;
                    $instancename = format_string($instancename, true, $course->id);
                    $linkcss = $mod->visible ? '' : ' class="dimmed" ';
                    if (!empty($modinfo->cms[$modnumber]->extra)) {
                        $extra = $modinfo->cms[$modnumber]->extra;
                    } else {
                        $extra = '';
                    }
                    if (!empty($modinfo->cms[$modnumber]->icon)) {
                        $icon = $OUTPUT->pix_url($modinfo->cms[$modnumber]->icon);
                    } else {
                        $icon = $OUTPUT->pix_url('icon', $mod->modname);
                    }

                    if ($mod->modname == 'label') {
                        $this->content->items[] = format_text($extra, FORMAT_HTML, $options).$editbuttons;
                        $this->content->icons[] = '';
                    } else {
                        //Accessibility: incidental image - should be empty Alt text
                        $icon = '<img src="'.$icon.'" class="icon" alt="" />&nbsp;';
                        $this->content->items[] = '<a title="'.$mod->modfullname.'" '.$linkcss.' '.$extra.
                            ' href="'.$CFG->wwwroot.'/mod/'.$mod->modname.'/view.php?id='.$mod->id.'">'.$icon.$instancename.'</a>'.$editbuttons;
                    }
                }
            }
        }

        if ($ismoving) {
            $this->content->items[] = '<a title="'.$strmovefull.'" href="'.$CFG->wwwroot.'/course/mod.php?movetosection='.$section->id.'&amp;sesskey='.sesskey().'">'.
                                      '<img style="height:16px; width:80px; border:0px" src="'.$OUTPUT->pix_url('movehere') . '" alt="'.$strmovehere.'" /></a>';
            $this->content->icons[] = '';
        }

        if ($modnames) {
            $this->content->footer = print_section_add_menus($course, 0, $modnames, true, true);
        } else {
            $this->content->footer = '';
        }

        return $this->content;
    }
}


