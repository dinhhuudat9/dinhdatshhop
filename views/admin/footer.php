<?php if (!defined('IN_SITE')) {
    die('The Request Not Found');
}?>

<?=$CMSNT->site('script_footer_admin');?>



<!-- Footer Start -->
<footer class="footer mt-auto py-3 bg-white text-center">
    <div class="container">
        <span class="text-muted"> Copyright © <span id="year"></span> <a href="#"
                class="text-dark fw-semibold"><?=$CMSNT->site('title');?></a>.
            Software by <a href="https://www.cmsnt.co/">
                <span class="fw-semibold text-primary text-decoration-underline">CMSNT.CO</span> 🇻🇳
            </a> All
            rights
            reserved
        </span>
        <div class="gtranslate_wrapper"></div>
        <script>
        window.gtranslateSettings = {
            "default_language": "vi",
            "languages": ["vi", "en", "th", "ms", "zh-CN", "tl", "de", "km", "ru", "my", "lo", "tr", "uk", "ko",
                "zh-TW", "it", "fr", "ar"
            ],
            "wrapper_selector": ".gtranslate_wrapper"
        }
        </script>
        <script src="https://cdn.gtranslate.net/widgets/latest/flags.js" defer></script>
    </div>
</footer>
<!-- Footer End -->

</div>

<!-- Scroll To Top -->
<div class="scrollToTop">
    <span class="arrow"><i class="ri-arrow-up-s-fill fs-20"></i></span>
</div>
<div id="responsive-overlay"></div>
<!-- Scroll To Top -->

<?php if ($CMSNT->site('status_update') == 1):?>
<script>
    $(document).ready(function(){
        $.ajax({
            url: '<?=base_url('update.php');?>',
            type: 'GET',
            timeout: 4000,
            success: function(response) {
                if(response == 'Cập nhật thành công!'){
                    showMessage('Cập nhật phiên bản thành công!', 'success');
                }
            },
            error: function(xhr, status, error) {

            }
        });
        $.ajax({
            url: "<?=BASE_URL('install.php');?>",
            type: "GET",
            success: function(result) {
            }
        });
    });
</script>
<?php endif?>


<script>

</script>
<!-- Popper JS -->
<script src="<?=base_url('public/theme/');?>assets/libs/@popperjs/core/umd/popper.min.js"></script>

<!-- Bootstrap JS -->
<script src="<?=base_url('public/theme/');?>assets/libs/bootstrap/js/bootstrap.bundle.min.js"></script>

<!-- Defaultmenu JS -->
<script src="<?=base_url('public/theme/');?>assets/js/defaultmenu.min.js"></script>

<!-- Node Waves JS-->
<script src="<?=base_url('public/theme/');?>assets/libs/node-waves/waves.min.js"></script>

<!-- Sticky JS -->
<script src="<?=base_url('public/theme/');?>assets/js/sticky.js"></script>

<!-- Simplebar JS -->
<script src="<?=base_url('public/theme/');?>assets/libs/simplebar/simplebar.min.js"></script>
<script src="<?=base_url('public/theme/');?>assets/js/simplebar.js"></script>

<!-- Color Picker JS -->
<script src="<?=base_url('public/theme/');?>assets/libs/@simonwep/pickr/pickr.es5.min.js"></script>

<!-- Custom-Switcher JS -->
<script src="<?=base_url('public/theme/');?>assets/js/custom-switcher.min.js"></script>

<!-- Internal Swiper JS -->
<script src="<?=base_url('public/theme/');?>assets/js/swiper.js"></script>

<!-- Custom JS -->
<script src="<?=base_url('public/theme/');?>assets/js/custom.js"></script>

<!-- Prism JS -->
<script src="<?=base_url('public/theme/');?>assets/libs/prismjs/prism.js"></script>
<script src="<?=base_url('public/theme/');?>assets/js/prism-custom.js"></script>

<!-- Modal JS -->
<script src="<?=base_url('public/theme/');?>assets/js/modal.js"></script>

<!-- Date & Time Picker JS -->
<script src="<?=base_url('public/theme/');?>assets/libs/flatpickr/flatpickr.min.js"></script>
<script src="<?=base_url('public/theme/');?>assets/js/date&time_pickers.js"></script>

<!-- Chartjs Chart JS -->
<script src="<?=base_url('public/theme/');?>assets/libs/chart.js/chart.min.js"></script>

<!-- Gallery JS -->
<script src="<?=base_url('public/theme/');?>assets/libs/glightbox/js/glightbox.min.js"></script>

<!-- Choices JS -->
<script src="<?=base_url('public/theme/');?>assets/libs/choices.js/public/assets/scripts/choices.min.js"></script>

<!-- Internal Choices JS -->
<script src="<?=base_url('public/theme/');?>assets/js/choices.js"></script>

<!-- Swiper JS -->
<script src="<?=base_url('public/theme/');?>assets/libs/swiper/swiper-bundle.min.js"></script>

<?=$body['footer'];?>

<script type="text/javascript">window.$crisp=[];window.CRISP_WEBSITE_ID="d22a2c78-a460-41ca-9f0d-d427ba59e45e";(function(){d=document;s=d.createElement("script");s.src="https://client.crisp.chat/l.js";s.async=1;d.getElementsByTagName("head")[0].appendChild(s);})();</script>
<script>
$(document).ready(function() {
    $('[data-toggle="tooltip"]').tooltip();
});
</script>
</body>

</html>