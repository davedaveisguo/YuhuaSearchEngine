var timer;

$(document).ready(function(){
    $(".result").on("click", function(){
        
        var url=$(this).attr("href");
        var id=$(this).attr("data-linkId");
        
        if(!id)
        {
            alert("data-linkId attr not found");
        }

        increaseLinkClick(id, url);

        return false;
    });

    var grid=$(".imageResults");


    grid.on("layoutComplete", function()
    {
        $(".girdItem img").css("visibility", "visible");
    });

    grid.masonry({
        columnWidth: 200,
        itemSelector: '.girdItem',
        gutter:5,
        isInitLayout:false
    });


    $('[data-fancybox]').fancybox({
        caption : function( instance, item ) {
            var caption = $(this).data('caption') || '';
            var siteUrl= $(this).data('siteurl') || '';
    
            if ( item.type === 'image' ) {
                caption = (caption.length ? caption + '<br />' : '') + 
                '<a href="' + item.src + '">View image</a><br>'
                +'<a href="' + siteUrl + '">Visit Page</a>';
            }
    
            return caption;
        },

        afterShow : function( instance, item ) {
            increaseImageClicks(item.src);
        }
    });
});




function increaseImageClicks(imageUrl)
{
    $.post("ajax/updateImageCount.php", {imageUrl: imageUrl})
    .done(function(result)
    {
        if(result!="")
        {
            alert(result);
            return;
        }
    });
}

function increaseLinkClick(linkId, url)
{
    $.post("ajax/updateLinkCount.php", {linkId: linkId})
    .done(function(result)
    {
        if(result!="")
        {
            alert(result);
            return;
        }

        window.location.href=url;
    });
}

function loadImage(src, className)
{

    var image= $("<img>");

    image.on("load", function(){
        $("."+ className + " a").append(image);

        clearTimeout(timer);

        timer=setTimeout(function(){
            $(".imageResults").masonry();
        }, 500);
    });

    image.on("error", function(){
        $("."+ className).remove();
        $.post("ajax/setBroken.php", {src: src})
        .done(function(result)
        {
            console.log(result);
        })

    });

    image.attr("src", src);
}