{if $report_name}
  {if $report_name == 'overview'}
    <h3>overview report
      {if $group_filter}
        - filtered by group {$group_filter}
      {/if}
    </h3>
  {else}
    <h3>{$report_name} donors
      {if $target_year}
        for {$target_year}
      {/if}
      {if $group_filter}
        - filtered by group {$group_filter}
      {/if}
    </h3>
  {/if}
{/if}

<div><br/>
   {$form.report_type.label} {$form.report_type.html} 
   {$form.target_year.label} {$form.target_year.html}
   {$form.group.label} {$form.group.html}
</div>
<div><br/>
  {include file="CRM/common/formButtons.tpl" location="bottom"}
</div>
{if $content}
  <br/><br/>
  <table>
    <tr class="columnHeader">
    {foreach from=$title item=titlevalue}
      <td>{$titlevalue}</td>
    {/foreach}
    </tr>
    {foreach from=$content item=row}
      <tr>
      {foreach from=$row item=column}
      <td>{$column}</td>
      {/foreach}
      </tr>
    {/foreach}
  </table>
{/if}

