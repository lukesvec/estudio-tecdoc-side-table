{if $data|@count}
	{counter direction=up assign="lvl"}
	{assign var="ident" value="lvl`$lvl`"}
    {if $lvl eq 1}
        <div class="tabs">
            <div class="tecdoc-tabs-header">
                <ul class="tecdoc-tabs">
                    {foreach from=$data item=item name=menu}
                        {assign var="class" value=""}
                        {if $item.prefered}
                            {assign var="class" value="`$class` prefered"}
                        {/if}
                        {if $smarty.foreach.menu.first}
                            {assign var="class" value="`$class` first"}
                        {/if}

                        {if $smarty.foreach.menu.last}
                            {assign var="class" value="`$class` last"}
                        {/if}
                        {if $selected.vehicleType eq 1 &&  $smarty.foreach.menu.first}
                            {assign var="class" value="`$class` selected"}
                        {/if}
                        {if $selected.vehicleType eq 2 &&  $smarty.foreach.menu.last}
                            {assign var="class" value="`$class` selected"}
                        {/if}
                        {if !$item.href}
                            {assign var="class" value="`$class` denied"}
                        {else}
                            {if $selected}
                                {if $item.href == $selected.url}
                                    {assign var="class" value="`$class` selected"}
                                {else}
                                    {if in_array($item.href, $selected.path)}
                                        {assign var="class" value="`$class` onpath"}
                                    {/if}
                                {/if}
                            {/if}
                        {/if}
                        <li {if $class}class="{$class}"{/if}>
                            <a title="{$item.title}" {if $item.href}href="{$item.href|default:'#'}"{/if} {if $item.target == '_blank'}{'class="js-target-blank"'}{/if}>{if $item.icon_file}<img src="{$item.icon_file}" title="{$item.icon_title}" alt="" class="menu-icon" /> {/if}{$item.text}</a>
                        </li>
                        {* set proper selected items *}
                        {if $item.tecdocBrands}
                            {assign var="tecdocItem" value=$item}
                        {/if}
                    {/foreach}
                </ul>

            </div>

            {* TecDoc znacky *}
            
            
            <div id='multiWrapper'>
			  <select id="sideTecBrand" class="js-states form-control js-change-url" name="tecdocBrands" >
			    <optgroup class='def-cursor' label='[e-shop.tecdoc-zvolte-znacku]'>
			      <option class="hiddeme">[e-shop.tecdoc-zvolte-znacku]</option>
			      {foreach from=$tecdocItem.tecdocBrands item=brand}
                        <option value="{if $brand.href}{$brand.href|default:'#'}{/if}" {if $brand.href == $tecdocItem.selectedBrandHref}selected="selected"{/if}>{$brand.text}</option>
                    {/foreach}
			    </optgroup>
			  </select>
			</div>
			
			<div id='multiWrapper'>
			  <select id="sideTecModel" class="js-states form-control js-change-url" name="tecdocModels" {if not $tecdocItem.tecdocModels.0}disabled="disabled"{/if}>
			    <optgroup class='def-cursor' label='[e-shop.tecdoc-zvolte-model]' data-date="Datum">
			      <option class="hiddeme">[e-shop.tecdoc-zvolte-model]</option>
			      {foreach from=$tecdocItem.tecdocModels item=model}
                    {if $model}
                        <option data-date="{$model.year_from}{if $model.year_to} - {$model.year_to}{/if}" value="{if $model.href}{$model.href|default:'#'}{/if}" {if $model.href == $tecdocItem.selectedModelHref}selected="selected"{/if}>{$model.text}</option>
                    {/if}
                 {/foreach}
			    </optgroup>
			  </select>
			</div>
			
			<div id='multiWrapper'>
			  <select id="sideTecMotor" class="js-states form-control js-change-url" name="tecdocModels" {if not $tecdocItem.tecdocMotorizations.0}disabled="disabled"{/if}>
			    <optgroup class='def-cursor' label='[e-shop.tecdoc-zvolte-motorizaci]' data-engine="Motor" data-kw="kW">
			      <option class="hiddeme">[e-shop.tecdoc-zvolte-motorizaci]</option>
			      {foreach from=$tecdocItem.tecdocMotorizations item=motorization}
                    {if $motorization}
                        <option value="{if $motorization.href}{$motorization.href|default:'#'}{/if}" {if $motorization.href == $tecdocItem.selectedMotorizationHref}selected="selected"{/if} data-engine="{$motorization.description}" data-kw="{$motorization.engine_output_kw}">{$motorization.text}</option>
                    {/if}
                 {/foreach}
			    </optgroup>
			  </select>
			</div>
			
			<div class="clearfix">&nbsp;</div>

            
            {* Hledani dle motoru *}
            {if $layout.enableEngineSearch}
            <div class="engine-search-menu">
                <div class="engine-search-menu-title">[e-shop.hledej-dle-motoru]</div>
                <form action="{$layout.pages.engine_search.url}">
                    <input class="js-switch" type="text" name="engine" data-placeholder="[formularova-pole.zadej-kod-motoru]" value="{$layout.pages.engine_search.value}">
                    <button class="engine-search-menu-button" type="submit" title="[formularova-pole.hledani-dle-motoru]"><i class="fa fa-search" aria-hidden="true"></i></button>
                </form>
            </div>
            {/if}
            
        </div>
    {/if}
    {counter direction=down}
{/if}
