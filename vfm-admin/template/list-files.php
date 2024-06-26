<?php
/**
 * VFM - veno file manager: include/list-files.php
 * list files inside current directory
 *
 * PHP version >= 5.3
 *
 * @category  PHP
 * @package   VenoFileManager
 * @author    Nicola Franchini <support@veno.it>
 * @copyright 2013 Nicola Franchini
 * @license   Exclusively sold on CodeCanyon
 * @link      http://filemanager.veno.it/
 */
if (!defined('VFM_APP')) {
    return;
}
/**
* List Files
*/
if ($gateKeeper->isAccessAllowed() && $location->editAllowed()) {
    if ($gateKeeper->isAllowed('view_enable')) {
        $listview = isset($_SESSION['listview']) ? $_SESSION['listview'] : $setUp->getConfig('list_view', 'list');
        if ($listview == 'grid') {
            $listclass = 'gridview';
            $switchclass = 'grid';
        } else {
            $listclass = 'listview';
            $switchclass = 'list';
        } ?>
    <div class="vfmblock col-12 mb-3">
    <section class="tableblock ghost ghost-hidden bg-light-lighter p-3 shadow-sm">
        <div class="action-group d-flex flex-wrap mb-3">
        <?php

        if ($gateKeeper->isAllowed('download_enable')
            || $gateKeeper->isAllowed('move_enable')
            || $gateKeeper->isAllowed('copy_enable')
            || $gateKeeper->isAllowed('delete_enable')
            || $gateKeeper->isAllowed('roto_enable')
        ) {
            ?>
            <div class="btn-group">
                <button type="button" class="btn btn-primary dropdown-toggle groupact" data-bs-toggle="dropdown">
                    <i class="bi bi-boxes"></i> 
                    <?php echo $setUp->getString("group_actions"); ?> 
                    <span class="caret"></span>
                </button>
                <ul class="dropdown-menu" role="menu">
                <?php
                if ($gateKeeper->isAllowed('download_enable')) { ?>
                    <li>
                        <a class="multid dropdown-item" href="#">
                            <i class="bi bi-cloud-download"></i> 
                            <?php echo $setUp->getString("download"); ?>
                        </a>
                    </li>
                    <?php
                }
                
				if ($gateKeeper->isAllowed('roto_enable') && !$gateKeeper->isMasterAdmin()) { ?>
                    <li>
                        <a class="multiroto dropdown-item" href="#">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-airplane" viewBox="0 0 16 16">
                                <path d="M6.428 1.151C6.708.591 7.213 0 8 0s1.292.592 1.572 1.151C9.861 1.73 10 2.431 10 3v3.691l5.17 2.585a1.5 1.5 0 0 1 .83 1.342V12a.5.5 0 0 1-.582.493l-5.507-.918-.375 2.253 1.318 1.318A.5.5 0 0 1 10.5 16h-5a.5.5 0 0 1-.354-.854l1.319-1.318-.376-2.253-5.507.918A.5.5 0 0 1 0 12v-1.382a1.5 1.5 0 0 1 .83-1.342L6 6.691V3c0-.568.14-1.271.428-1.849m.894.448C7.111 2.02 7 2.569 7 3v4a.5.5 0 0 1-.276.447l-5.448 2.724a.5.5 0 0 0-.276.447v.792l5.418-.903a.5.5 0 0 1 .575.41l.5 3a.5.5 0 0 1-.14.437L6.708 15h2.586l-.647-.646a.5.5 0 0 1-.14-.436l.5-3a.5.5 0 0 1 .576-.411L15 11.41v-.792a.5.5 0 0 0-.276-.447L9.276 7.447A.5.5 0 0 1 9 7V3c0-.432-.11-.979-.322-1.401C8.458 1.159 8.213 1 8 1s-.458.158-.678.599"/>
                            </svg>
                            <?php echo $setUp->getString("ROTO"); ?>
                        </a>
                    </li>
                    <?php
                }
                if ($gateKeeper->isAllowed('move_enable')) { ?>
                    <li>
                        <a class="multimove dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#archive-map-move" data-action="move">
                            <i class="bi bi-arrow-right"></i> 
                            <?php echo $setUp->getString("move"); ?>
                        </a>
                    </li>
                    <?php
                }
                if ($gateKeeper->isAllowed('copy_enable')) { ?>
                   <li>
                        <a class="multicopy dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#archive-map-copy" data-action="copy">
                            <i class="bi bi-clipboard-check"></i> 
                            <?php echo $setUp->getString("copy"); ?>
                        </a>
                    </li>
                    <?php
                }
                if ($gateKeeper->isAllowed('delete_enable')) { ?>
                    <li><a class="multic dropdown-item" href="#">
                            <i class="bi bi-trash"></i> 
                            <?php echo $setUp->getString("delete"); ?>
                        </a>
                    </li>
                    <?php
                } ?>
                </ul>
            </div> <!-- .btn-group -->
            <?php
            if ($gateKeeper->isAllowed('sendfiles_enable') && $gateKeeper->isAllowed('download_enable')) { ?>
            <button class="btn btn-primary manda">
                <i class="bi bi-send"></i> 
                <?php echo $setUp->getString("share"); ?>
            </button>
                <?php
            } ?>
            <?php
        }
        ?>
            <div class="switchview ms-auto <?php echo $switchclass; ?>" title="<?php echo $setUp->getString("view"); ?>"></div>
        </div> <!-- .action-group -->

        <form id="tableform">
            <table id="filetable" class="w-100 table <?php echo $listclass; ?>" cellspacing="0">
                <thead>
                    <tr class="rowa one">
                        <td class="text-center">
                            <a href="#" title="<?php echo $setUp->getString("select_all"); ?>" id="selectall">
                                <i class="bi bi-check2-all"></i>
                            </a>
                        </td>
                        <td class="icon"></td>
                        <td class="small h-filename">
                            <span class="d-md-none sorta nowrap">
                                <i class="bi bi-sort-alpha-down"></i>
                            </span>
                            <span class="d-none d-md-inline sorta nowrap">
                                <?php echo $setUp->getString("file_name"); ?>
                            </span>
                        </td>
                        <td class="taglia reduce small h-filesize d-none d-md-table-cell">
                            <span class="text-center sorta nowrap">
                                <?php echo $setUp->getString("size"); ?>
                            </span>
                        </td>
                        <td class="reduce small h-filedate d-none d-md-table-cell">
                            <span class="text-center sorta nowrap">
                                <?php echo $setUp->getString("last_changed"); ?>
                            </span>
                        </td>
                    <?php
                    if ($gateKeeper->isAllowed('rename_enable')) { ?>
                        <td class="small text-center gridview-hidden d-none d-md-table-cell">
                            <i class="bi bi-pencil"></i>
                        </td>
                        <?php
                    } ?>
                    <td class="small text-center gridview-hidden">
                    <?php
                    if ($gateKeeper->isAllowed('delete_enable')) {  ?>
                        <i class="bi bi-trash d-none d-md-block"></i>
                        <?php
                    } ?>
                     </td>
                    </tr>
                </thead>
                <tbody class="gridbody"></tbody>
            </table>
        </form>
    </section>

    <div class="position-absolute w-100 h-100 start-0 top-0 d-flex align-items-center justify-content-center overload">
        <div class="spinner-border" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
    </div>
</div>
        <?php
    }
    $empty_icon = 'folder2-open';
    $wrapper_id = 'bigfolder';
    if ($gateKeeper->isAllowed('upload_enable')) {
        $empty_icon = 'cloud-upload';
        $wrapper_id = 'biguploader';
    }
    ?>
<div class="col-12 hidetable d-none">
<section class="text-center p-4 bg-light-lighter shadow-sm">

    <div class="alpha-light display-1 py-5" id="<?php echo $wrapper_id; ?>">
        <i class="bi bi-<?php echo $empty_icon; ?>"></i>
    </div>
</section>
</div>
    <?php
} // end access allowed
