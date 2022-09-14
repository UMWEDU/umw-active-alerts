umw_active_alerts_vars = umw_active_alerts_vars || {};
import UmwActiveAlerts from "./classes/umw-active-alerts";

document.addEventListener('heartbeat-send', (event, data) => {
    data.umwalerts = umw_active_alerts_vars;
});

document.addEventListener('heartbeat-tick', (event, data) => {
    if (!data.umwalerts || data.umwalerts.length <= 0) {
        return;
    }

    Object.keys(data.umwalerts).forEach((alert) => {
        const el = new UmwActiveAlerts(data.umwalerts[alert][0], alert);
        const ob = el.render();

        if (null === ob) {
            return false;
        }

        switch (alert) {
            case 'emergency' :
            case 'advisory' :
                document.querySelector('body').prepend(ob);
                break;
            default :
                document.querySelector('body').append(ob);
                break;
        }
    });
});