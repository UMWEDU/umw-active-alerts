import UmwAdvisory from "./components/umw-advisory";
import UmwEmergency from "./components/umw-emergency";

class UmwActiveAlerts {
    props = {};
    key = '';

    constructor(props, key) {
        this.props = props;
        this.type = key;
    }

    render() {
        switch (this.key) {
            case 'emergency' :
                return (
                    <UmwEmergency {...this.props}></UmwEmergency>
                );
            case 'advisory' :
                return (
                    <UmwAdvisory {...this.props}></UmwAdvisory>
                );
            default :
                return (
                    <UmwLocalAlert {...this.props}></UmwLocalAlert>
                );
        }
    }
}

export default UmwActiveAlerts;