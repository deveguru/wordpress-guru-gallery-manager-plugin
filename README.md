# wordpress-guru-gallery-manager-plugin
Guru gallery manager and show with Shortcodes for Wordpress

The key changes that were made are as follows:

Enable page-attributes: When registering the Gallery Post Type, we enable the page-attributes feature. This makes the menu_order field available for each gallery, which is used to store the order.
Modify the shortcode query: We change the query that calls the galleries to display on the frontend to sort by menu_order.
Add a “sort” column to the gallery list: We add a new column to the gallery list in the admin panel to display the current order and make it sortable.
Implement Drag-and-Drop: Using jQuery UI Sortable and AJAX, we implement drag-and-drop functionality to sort galleries on the list page.

Summary of changes and usage

Enable sorting:
In the register_post_type method, the value of 'supports' was changed to ['title', 'page-attributes'] . This causes WordPress to consider a menu_order field in the database for each gallery.

Sorting on the frontend:
In the render_shortcode method, the get_posts query was changed to sort the galleries by menu_order (from low to high) ('orderby' => 'menu_order', 'order' => 'ASC').

Admin panel improvements:
New “Order” column: A column called “Order” has been added to the gallery list in the “Gallery Guru -> All Galleries” path, which displays the order number of each gallery.
Drag-and-Drop capability: You can now click on any row on the “All Galleries” page, drag it up or down, and drop it in the desired position. The order is saved automatically via AJAX. No need to click the save button.
Column Sorting: You can click on the “Sort” column heading to sort the galleries by it.

How to Use:

After updating the plugin code, simply go to Gallery Guru -> All Galleries in your WordPress dashboard. Hover over one of the galleries; you will see the cursor change to “move”. Now you can drag that row up or down and drop it in the desired position to change its display order in the main shortcode. These changes are saved and applied to the site immediately.



مدیریت گالری گورو و نمایش آن با کدهای کوتاه برای وردپرس


تغییرات کلیدی که اعمال شد به شرح زیر است:


    فعال‌سازی page-attributes: در هنگام ثبت Post Type گالری، قابلیت page-attributes را فعال می‌کنیم. این کار فیلد menu_order را برای هر گالری در دسترس قرار می‌دهد که برای ذخیره ترتیب استفاده می‌شود.
    اصلاح کوئری شورت‌کد: کوئری که گالری‌ها را برای نمایش در فرانت‌اند فراخوانی می‌کند را تغییر می‌دهیم تا بر اساس menu_order مرتب‌سازی کند.
    افزودن ستون “ترتیب” به لیست گالری‌ها: یک ستون جدید به لیست گالری‌ها در پنل مدیریت اضافه می‌کنیم تا ترتیب فعلی را نمایش دهد و آن را قابل مرتب‌سازی (Sortable) می‌کنیم.
    پیاده‌سازی Drag-and-Drop: با استفاده از jQuery UI Sortable و AJAX، قابلیت کشیدن و رها کردن را برای مرتب‌سازی گالری‌ها در صفحه لیست پیاده‌سازی می‌کنیم.

خلاصه تغییرات و نحوه استفاده

    فعال‌سازی ترتیب‌دهی:
        در متد register_post_type، مقدار 'supports' به ['title', 'page-attributes'] تغییر یافت. این کار باعث می‌شود وردپرس برای هر گالری یک فیلد menu_order در دیتابیس در نظر بگیرد.

    مرتب‌سازی در فرانت‌اند:
        در متد render_shortcode، کوئری get_posts به نحوی تغییر کرد که گالری‌ها را بر اساس menu_order (از کم به زیاد) مرتب کند ('orderby' => 'menu_order', 'order' => 'ASC').

    بهبودهای پنل مدیریت:
        ستون جدید “ترتیب”: یک ستون به نام “ترتیب” (Order) به لیست گالری‌ها در مسیر “گالری گورو -> همه گالری‌ها” اضافه شده است که شماره ترتیب هر گالری را نمایش می‌دهد.
        قابلیت Drag-and-Drop: اکنون می‌توانید در همان صفحه “همه گالری‌ها”، روی هر سطر کلیک کرده، آن را به بالا یا پایین بکشید و در موقعیت دلخواه رها کنید. ترتیب به صورت خودکار و از طریق AJAX ذخیره می‌شود. نیازی به کلیک روی دکمه ذخیره نیست.
        مرتب‌سازی ستون: می‌توانید روی عنوان ستون “ترتیب” کلیک کنید تا گالری‌ها بر اساس آن مرتب شوند.

نحوه استفاده:

پس از به‌روزرسانی کد افزونه، به سادگی به بخش گالری گورو -> همه گالری‌ها در پیشخوان وردپرس خود بروید. نشانگر ماوس را روی یکی از گالری‌ها ببرید؛ خواهید دید که نشانگر به شکل “حرکت” (move) در می‌آید. اکنون می‌توانید آن ردیف را به بالا یا پایین بکشید و در جایگاه مورد نظر خود رها کنید تا ترتیب نمایش آن در شورت‌کد اصلی تغییر کند. این تغییرات بلافاصله ذخیره و در سایت اعمال می‌شوند.




<img width="1255" height="359" alt="image" src="https://github.com/user-attachments/assets/0092f1d1-d27c-4311-81ba-163a3de831ee" />


<img width="1252" height="443" alt="image" src="https://github.com/user-attachments/assets/4b307212-6960-46b0-8e20-15715e64c673" />
