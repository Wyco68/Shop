import http from 'k6/http';
import { check, sleep } from 'k6';

const BASE_URL = __ENV.BASE_URL || 'http://localhost';

export const options = {
  stages: [
    { duration: '30s', target: 50 },
    { duration: '1m', target: 100 },
    { duration: '30s', target: 0 },
  ],
  thresholds: {
    http_req_duration: ['p(95)<800'],
    http_req_failed: ['rate<0.05'],
  },
};

export default function () {
  const browse = http.get(`${BASE_URL}/products`);
  check(browse, { 'products 200': (r) => r.status === 200 });

  const home = http.get(`${BASE_URL}/`);
  check(home, { 'home 200': (r) => r.status === 200 });

  sleep(1);
}
