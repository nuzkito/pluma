import axios from "axios";
import EasyMDE from "easymde";

window.axios = axios;
window.axios.defaults.headers.common["X-Requested-With"] = "XMLHttpRequest";

window.EasyMDE = EasyMDE;
