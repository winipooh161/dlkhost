import{i as u,g as p,o as m,a as b}from"./index.esm2017-NZQW5Whv.js";let l=null,s=null,c=!1;document.addEventListener("DOMContentLoaded",()=>{if(l)return;console.log("Инициализация Firebase..."),l=u({apiKey:"AIzaSyB6N1n8dW95YGMMuTsZMRnJY1En7lK2s2M",authDomain:"dlk-diz.firebaseapp.com",projectId:"dlk-diz",storageBucket:"dlk-diz.firebasestorage.app",messagingSenderId:"209164982906",appId:"1:209164982906:web:0836fbb02e7effd80679c3"}),s=p(l),g(),h()});function g(){s&&m(s,e=>{var t,o,i,n,a,d;console.log("Получено сообщение в активном окне:",e),v(((t=e.notification)==null?void 0:t.title)||"Новое сообщение",((o=e.notification)==null?void 0:o.body)||"",e.data),x(((i=e.notification)==null?void 0:i.title)||"Новое сообщение",((n=e.notification)==null?void 0:n.body)||"",(a=e.data)==null?void 0:a.chatId,(d=e.data)==null?void 0:d.chatType)})}function h(){if(!("Notification"in window)){console.log("Этот браузер не поддерживает уведомления");return}const e=Notification.permission;console.log("Текущее разрешение для уведомлений:",e),e==="granted"?f():e!=="denied"&&w()}function w(){if(document.querySelector(".notification-permission-banner"))return;const e=document.createElement("div");e.className="notification-permission-banner",e.innerHTML=`
        <div class="notification-banner-content">
            <p>Разрешите уведомления, чтобы быть в курсе новых сообщений</p>
            <button id="allow-notifications" class="btn btn-primary">Разрешить</button>
            <button id="close-notification-banner" class="btn btn-secondary">Позже</button>
        </div>
    `,document.body.appendChild(e),document.getElementById("allow-notifications").addEventListener("click",()=>{Notification.requestPermission().then(t=>{console.log("Пользователь "+(t==="granted"?"разрешил":"не разрешил")+" уведомления"),e.remove(),t==="granted"&&f()})}),document.getElementById("close-notification-banner").addEventListener("click",()=>{e.remove()})}function f(){if(!("serviceWorker"in navigator)){console.error("Service Worker не поддерживается в этом браузере");return}if(c){console.log("Токен уже был сохранен ранее");return}console.log("Регистрация Service Worker..."),navigator.serviceWorker.register("/firebase-messaging-sw.js").then(e=>(console.log("Service Worker зарегистрирован:",e.scope),b(s,{vapidKey:"BLf08mEO3lePyBvZCwTzaSNX9R981qwESUblCemdDVZUT_cs4G3GD2YY38CN8ELIcPmgVRZ92G7ePzY187d4Dh4",serviceWorkerRegistration:e}))).then(e=>{e?(console.log("Получен FCM токен:",e.substring(0,20)+"..."),y(e),c=!0):(console.error("Не удалось получить токен FCM"),Notification.permission!=="granted"&&console.error("Причина: не получено разрешение на уведомления"))}).catch(e=>{console.error("Ошибка при регистрации сервис-воркера или получении токена:",e)})}function y(e){c||fetch("/firebase/update-token",{method:"POST",headers:{"Content-Type":"application/json","X-CSRF-TOKEN":document.querySelector('meta[name="csrf-token"]').getAttribute("content")},body:JSON.stringify({token:e})}).then(t=>{t.ok?(console.log("Токен успешно сохранен на сервере"),c=!0):console.error("Ошибка при сохранении токена на сервере")}).catch(t=>{console.error("Ошибка при отправке токена:",t)})}function v(e,t,o={}){if(!("Notification"in window)){console.log("Этот браузер не поддерживает уведомления");return}if(Notification.permission==="granted")try{const i=new Notification(e,{body:t,icon:"/path/to/icon.png",tag:`chat-${(o==null?void 0:o.chatId)||"general"}`,data:o,requireInteraction:!0,renotify:!0});return i.onclick=function(){if(window.focus(),o!=null&&o.chatId&&(o!=null&&o.chatType))if(window.location.pathname.includes("/chats")){const n=document.querySelector(`[data-chat-id="${o.chatId}"][data-chat-type="${o.chatType}"]`);n&&n.click()}else window.location.href=`/chats?chatId=${o.chatId}&chatType=${o.chatType}`;i.close()},i.onerror=function(n){console.error("Ошибка показа уведомления:",n)},i}catch(i){console.error("Ошибка при создании браузерного уведомления:",i)}}function x(e,t,o,i){const n=document.createElement("div");if(n.classList.add("firebase-notification"),n.innerHTML=`
        <div class="notification-content">
            <div class="notification-header">
                <h4>${e}</h4>
                <button class="close-notification">&times;</button>
            </div>
            <div class="notification-body">
                <p>${t}</p>
            </div>
            <div class="notification-footer">
                <button class="view-message-btn">Перейти к сообщению</button>
            </div>
        </div>
    `,document.body.appendChild(n),!document.getElementById("firebase-notification-styles")){const r=document.createElement("style");r.id="firebase-notification-styles",r.textContent=`
            .firebase-notification {
                position: fixed;
                bottom: 20px;
                right: 20px;
                background: #fff;
                border-radius: 8px;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
                width: 320px;
                z-index: 9999;
                animation: slideIn 0.3s ease-out;
                overflow: hidden;
                border-left: 4px solid #4285F4;
            }
            
            @keyframes slideIn {
                from { transform: translateX(100%); opacity: 0; }
                to { transform: translateX(0); opacity: 1; }
            }
            
            @keyframes slideOut {
                from { transform: translateX(0); opacity: 1; }
                to { transform: translateX(100%); opacity: 0; }
            }
            
            .notification-content {
                padding: 15px;
            }
            
            .notification-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 10px;
            }
            
            .notification-header h4 {
                margin: 0;
                font-size: 16px;
                font-weight: 600;
                color: #333;
            }
            
            .close-notification {
                background: none;
                border: none;
                font-size: 20px;
                cursor: pointer;
                color: #999;
                padding: 0;
            }
            
            .notification-body p {
                margin: 0 0 15px 0;
                color: #555;
                font-size: 14px;
            }
            
            .view-message-btn {
                background-color: #4285F4;
                color: white;
                border: none;
                padding: 6px 12px;
                border-radius: 4px;
                cursor: pointer;
                font-size: 13px;
                transition: background-color 0.2s;
            }
            
            .view-message-btn:hover {
                background-color: #3367D6;
            }
            
            .notification-permission-banner {
                position: fixed;
                top: 10px;
                left: 50%;
                transform: translateX(-50%);
                background-color: #f8f9fa;
                border: 1px solid #dee2e6;
                border-radius: 5px;
                padding: 15px;
                box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
                z-index: 1001;
                width: 90%;
                max-width: 500px;
            }
            .notification-permission-banner .notification-banner-content {
                display: flex;
                justify-content: space-between;
                align-items: center;
                flex-wrap: wrap;
            }
            .notification-permission-banner p {
                margin: 0 0 10px 0;
                flex: 100%;
            }
            .notification-permission-banner .btn {
                margin-right: 10px;
                padding: 5px 15px;
                border-radius: 3px;
                cursor: pointer;
            }
            .btn-primary {
                background-color: #007bff;
                color: white;
                border: none;
            }
            .btn-secondary {
                background-color: #6c757d;
                color: white;
                border: none;
            }
        `,document.head.appendChild(r)}n.querySelector(".close-notification").addEventListener("click",()=>{n.style.animation="slideOut 0.3s ease-in forwards",setTimeout(()=>n.remove(),300)}),n.querySelector(".view-message-btn").addEventListener("click",()=>{if(window.location.pathname.includes("/chats")){if(o&&i){const r=document.querySelector(`[data-chat-id="${o}"][data-chat-type="${i}"]`);r&&r.click()}}else{let r="/chats";o&&i&&(r+=`?chatId=${o}&chatType=${i}`),window.location.href=r}n.style.animation="slideOut 0.3s ease-in forwards",setTimeout(()=>n.remove(),300)}),setTimeout(()=>{document.body.contains(n)&&(n.style.animation="slideOut 0.3s ease-in forwards",setTimeout(()=>{document.body.contains(n)&&n.remove()},300))},8e3)}
