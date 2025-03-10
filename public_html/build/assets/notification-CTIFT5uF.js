import"./firebase-init-WlDQV2z_.js";import"./index.esm2017-NZQW5Whv.js";document.addEventListener("DOMContentLoaded",()=>{if("Notification"in window&&Notification.permission!=="granted"&&Notification.permission!=="denied"){const n=document.createElement("div");n.className="notification-permission-banner",n.innerHTML=`
            <div class="notification-banner-content">
                <p>Разрешите уведомления, чтобы быть в курсе новых сообщений</p>
                <button id="allow-notifications" class="btn btn-primary">Разрешить</button>
                <button id="close-notification-banner" class="btn btn-secondary">Позже</button>
            </div>
        `,document.body.appendChild(n),document.getElementById("allow-notifications").addEventListener("click",()=>{Notification.requestPermission().then(t=>{console.log("Пользователь "+(t==="granted"?"разрешил":"не разрешил")+" уведомления"),n.remove()})}),document.getElementById("close-notification-banner").addEventListener("click",()=>{n.remove()})}});
