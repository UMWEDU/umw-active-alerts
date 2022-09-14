class UmwEmergency extends React.Component {
    constructor(props) {
        super(props);
    }

    render() {
        const {title, id, link, content, excerpt, date, acf} = this.props;
        const {_advisory_expires_time} = acf;

        const curDate = new Date();
        const expiry = new Date(_advisory_expires_time);

        if (curDate > expiry) {
            return null;
        }

        const publishDate = new Date(date);

        let containerClass = 'umw-alert emergency-alert alert-id-' + id;

        return (
            <aside className={containerClass}>
                <header className="alert-heading">
                    <h2>{title.rendered}</h2>
                </header>
                {
                    content || excerpt ?
                        <div className="alert-body">
                            {content ? content.rendered : excerpt.rendered}
                        </div> : null
                }
                <footer className="alert-meta">
                    <p>Posted {publishDate.toLocaleString('en-us', {dateStyle: 'long', timeStyle: 'short'})}</p>
                </footer>
            </aside>
        )
    }
}

export default UmwEmergency;