import React, { useState, useEffect } from "react";
import {
    Row,
    Col,
} from "antd";
import axios from "axios";
import moment from "moment";
import dayjs from "dayjs";

import PasienRujukanDetailDiagnosaListIDRG from "./PasienRujukanDetailDiagnosaListIDRG";
import PasienRujukanDetailProcedureListIDRG from "./PasienRujukanDetailProcedureListIDRG";

function Index({ pasien }) {
    return (
        <>
            <p><strong>iDRG</strong></p>
            <Row gutter={[5, 5]}>
                <Col span={12}>
                    <PasienRujukanDetailDiagnosaListIDRG pasien={pasien} />
                </Col>
                <Col span={12}>
                    <PasienRujukanDetailProcedureListIDRG pasien={pasien} />
                </Col>
            </Row>
        </>
    );
}

export default Index;