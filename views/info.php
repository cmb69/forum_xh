<h4><?=$this->text('syscheck_title')?></h4>
<?php foreach ($this->checks as $check):?>
    <p class="xh_<?=$this->escape($check->state)?>"><?=$this->text('syscheck_message', $check->label, $check->stateLabel)?></p>
<?php endforeach?>
<h4><?=$this->text('about')?></h4>
<img src="<?=$this->logo()?>" style="float: left; width: 128px; height: 128px; margin-right: 16px"
alt="Plugin Icon"/>
<p>Version: <?=$this->version()?></p>
<p>Copyright © 2012-2017 Christoph M. Becker</p>
<p style="text-align: justify">
    Forum_XH is free software: you can redistribute it and/or modify it under
    the terms of the GNU General Public License as published by the Free
    Software Foundation, either version 3 of the License, or (at your option)
    any later version.
</p>
<p style="text-align: justify">
    Forum_XH is distributed in the hope that it will be useful, but <em>without
    any warranty</em>; without even the implied warranty of
    <em>merchantability</em> or <em>fitness for a particular purpose</em>. See
    the GNU General Public License for more details.
</p>
<p style="text-align: justify">
    You should have received a copy of the GNU General Public License along with
    Forum_XH. If not, see http://www.gnu.org/licenses/.
</p>
